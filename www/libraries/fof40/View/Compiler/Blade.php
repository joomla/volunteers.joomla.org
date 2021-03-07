<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\View\Compiler;

defined('_JEXEC') || die;

use FOF40\Container\Container;

class Blade implements CompilerInterface
{
	/**
	 * Are the results of this engine cacheable?
	 *
	 * @var bool
	 */
	protected $isCacheable = true;

	/**
	 * The extension of the template files supported by this compiler
	 *
	 * @var    string
	 * @since  3.3.1
	 */
	protected $fileExtension = 'blade.php';

	/**
	 * All of the registered compiler extensions.
	 *
	 * @var array
	 */
	protected $extensions = [];

	/**
	 * The file currently being compiled.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * All of the available compiler functions. Each one is called against every HTML block in the template.
	 *
	 * @var array
	 */
	protected $compilers = [
		'Extensions',
		'Statements',
		'Comments',
		'Echos',
	];

	/**
	 * Array of opening and closing tags for escaped echos.
	 *
	 * @var array
	 */
	protected $contentTags = ['{{', '}}'];

	/**
	 * Array of opening and closing tags for escaped echos.
	 *
	 * @var array
	 */
	protected $escapedTags = ['{{{', '}}}'];

	/**
	 * Array of footer lines to be added to template.
	 *
	 * @var array
	 */
	protected $footer = [];

	/**
	 * Counter to keep track of nested forelse statements.
	 *
	 * @var int
	 */
	protected $forelseCounter = 0;

	/**
	 * The FOF container we are attached to
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Should I use the PHP Tokenizer extension to compile Blade templates? Default is true and is preferable. We expect
	 * this to be false only on bad quality hosts. It can be overridden with Reflection for testing purposes.
	 *
	 * @var bool
	 */
	protected $usingTokenizer = false;

	public function __construct(Container $container)
	{
		$this->container      = $container;
		$this->usingTokenizer = false;

		if (!function_exists('token_get_all'))
		{
			return;
		}

		if (!defined('T_INLINE_HTML'))
		{
			return;
		}

		$this->usingTokenizer = true;
	}

	/**
	 * Report if the PHP Tokenizer extension is being used
	 *
	 * @return  bool
	 */
	public function isUsingTokenizer(): bool
	{
		return $this->usingTokenizer;
	}

	/**
	 * Are the results of this compiler engine cacheable? If the engine makes use of the forcedParams it must return
	 * false.
	 *
	 * @return  bool
	 */
	public function isCacheable(): bool
	{
		if (defined('JDEBUG') && JDEBUG)
		{
			return false;
		}

		return $this->isCacheable;
	}

	/**
	 * Compile a view template into PHP and HTML
	 *
	 * @param   string  $path         The absolute filesystem path of the view template
	 * @param   array   $forceParams  Any parameters to force (only for engines returning raw HTML)
	 *
	 * @return string The compiled result
	 */
	public function compile(?string $path, array $forceParams = []): string
	{
		if (empty($path))
		{
			return '';
		}

		$this->footer = [];

		$fileData = @file_get_contents($path);

		$this->setPath($path);

		return $this->compileString($fileData);
	}


	/**
	 * Get the path currently being compiled.
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * Set the path currently being compiled.
	 *
	 * @param   string  $path
	 *
	 * @return void
	 */
	public function setPath(string $path): void
	{
		$this->path = $path;
	}

	/**
	 * Compile the given Blade template contents.
	 *
	 * @param   string  $value
	 *
	 * @return string
	 */
	public function compileString(string $value): string
	{
		$result = '';

		if ($this->usingTokenizer)
		{
			// Here we will loop through all of the tokens returned by the Zend lexer and
			// parse each one into the corresponding valid PHP. We will then have this
			// template as the correctly rendered PHP that can be rendered natively.
			foreach (token_get_all($value) as $token)
			{
				$result .= is_array($token) ? $this->parseToken($token) : $token;
			}
		}
		else
		{
			foreach ($this->compilers as $type)
			{
				$value = $this->{"compile{$type}"}($value);
			}

			$result .= $value;
		}

		// If there are any footer lines that need to get added to a template we will
		// add them here at the end of the template. This gets used mainly for the
		// template inheritance via the extends keyword that should be appended.
		if (count($this->footer) > 0)
		{
			$result = ltrim($result, PHP_EOL)
				. PHP_EOL . implode(PHP_EOL, array_reverse($this->footer));
		}

		return $result;
	}

	/**
	 * Compile the default values for the echo statement.
	 *
	 * @param   string  $value
	 *
	 * @return string
	 */
	public function compileEchoDefaults(string $value): string
	{
		return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
	}

	/**
	 * Register a custom Blade compiler. Using a tag or not changes the behavior of this method when you try to redefine
	 * an existing custom Blade compiler.
	 *
	 * If you use a tag which already exists the old compiler is replaced by the new one you are defining.
	 *
	 * If you do not use a tag, the new compiler you are defining will always be added to the bottom of the list. That
	 * is to say, if another compiler would be matching the same function name (e.g. `@foobar`) it would get compiled by
	 * the first compiler, the one already set, not the one you are defining now. You are suggested to always use a tag
	 * for this reason.
	 *
	 * Finally, note that custom Blade compilers are compiled last. This means that you cannot override a core Blade
	 * compiler with a custom one. If you need to do that you need to create a new Compiler class -- probably extending
	 * this one -- and override the protected compiler methods. Remember to also create a custom Container and override
	 * its 'blade' key with a callable which returns an object of your custom class.
	 *
	 * @param   callable  $compiler
	 * @param   string    $tag  Optional. Give the callable a tag you can look for with hasExtensionByName
	 *
	 * @return void
	 */
	public function extend(callable $compiler, ?string $tag = null)
	{
		if (!is_null($tag))
		{
			$this->extensions[$tag] = $compiler;

			return;
		}

		$this->extensions[] = $compiler;
	}

	/**
	 * Look if a custom Blade compiler exists given its optional tag name.
	 *
	 * @param   string  $tag
	 *
	 * @return  bool
	 */
	public function hasExtension(string $tag): bool
	{
		return array_key_exists($tag, $this->extensions);
	}

	/**
	 * Remove a custom BLade compiler given its optional tag name
	 *
	 * @param   string  $tag
	 */
	public function removeExtension(string $tag): void
	{
		if (!$this->hasExtension($tag))
		{
			return;
		}

		unset ($this->extensions[$tag]);
	}

	/**
	 * Get the regular expression for a generic Blade function.
	 *
	 * @param   string  $function
	 *
	 * @return string
	 */
	public function createMatcher(string $function): string
	{
		return '/(?<!\w)(\s*)@' . $function . '(\s*\(.*\))/';
	}

	/**
	 * Get the regular expression for a generic Blade function.
	 *
	 * @param   string  $function
	 *
	 * @return string
	 */
	public function createOpenMatcher(string $function): string
	{
		return '/(?<!\w)(\s*)@' . $function . '(\s*\(.*)\)/';
	}

	/**
	 * Create a plain Blade matcher.
	 *
	 * @param   string  $function
	 *
	 * @return string
	 */
	public function createPlainMatcher(string $function): string
	{
		return '/(?<!\w)(\s*)@' . $function . '(\s*)/';
	}

	/**
	 * Sets the escaped content tags used for the compiler.
	 *
	 * @param   string  $openTag
	 * @param   string  $closeTag
	 *
	 * @return void
	 */
	public function setEscapedContentTags(string $openTag, string $closeTag): void
	{
		$this->setContentTags($openTag, $closeTag, true);
	}

	/**
	 * Gets the content tags used for the compiler.
	 *
	 * @return array
	 */
	public function getContentTags(): array
	{
		return $this->getTags();
	}

	/**
	 * Sets the content tags used for the compiler.
	 *
	 * @param   string  $openTag
	 * @param   string  $closeTag
	 * @param   bool    $escaped
	 *
	 * @return void
	 */
	public function setContentTags(string $openTag, string $closeTag, bool $escaped = false): void
	{
		$property = $escaped ? 'escapedTags' : 'contentTags';

		$this->{$property} = [preg_quote($openTag), preg_quote($closeTag)];
	}

	/**
	 * Gets the escaped content tags used for the compiler.
	 *
	 * @return array
	 */
	public function getEscapedContentTags(): array
	{
		return $this->getTags(true);
	}

	/**
	 * Returns the file extension supported by this compiler
	 *
	 * @return  string
	 *
	 * @since   3.3.1
	 */
	public function getFileExtension(): string
	{
		return $this->fileExtension;
	}

	/**
	 * Parse the tokens from the template.
	 *
	 * @param   array  $token  The token definition as an array of shape [tokenID, content].
	 *
	 * @return string
	 */
	protected function parseToken(array $token): string
	{
		[$id, $content] = $token;

		if ($id == T_INLINE_HTML)
		{
			foreach ($this->compilers as $type)
			{
				$content = $this->{"compile{$type}"}($content);
			}
		}

		return $content;
	}

	/**
	 * Execute the user defined extensions.
	 *
	 * @param   string  $value
	 *
	 * @return string
	 */
	protected function compileExtensions(string $value): string
	{
		foreach ($this->extensions as $compiler)
		{
			$value = call_user_func($compiler, $value, $this);
		}

		return $value;
	}

	/**
	 * Compile Blade comments into valid PHP.
	 *
	 * @param   string  $value
	 *
	 * @return string
	 */
	protected function compileComments(string $value): string
	{
		$pattern = sprintf('/%s--((.|\s)*?)--%s/', $this->contentTags[0], $this->contentTags[1]);

		return preg_replace($pattern, '<?php /*$1*/ ?>', $value);
	}

	/**
	 * Compile Blade echos into valid PHP.
	 *
	 * @param   string  $value
	 *
	 * @return string
	 */
	protected function compileEchos(string $value): string
	{
		$difference = strlen($this->contentTags[0]) - strlen($this->escapedTags[0]);

		if ($difference > 0)
		{
			return $this->compileEscapedEchos($this->compileRegularEchos($value));
		}

		return $this->compileRegularEchos($this->compileEscapedEchos($value));
	}

	/**
	 * Compile Blade Statements that start with "@"
	 *
	 * @param   string  $value
	 *
	 * @return mixed
	 */
	protected function compileStatements(string $value): string
	{
		return preg_replace_callback('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', [
			$this, 'compileStatementsCallback',
		], $value);
	}

	/**
	 * Callback for compileStatements, since $this is not allowed in Closures under PHP 5.3.
	 *
	 * @param   $match
	 *
	 * @return  string
	 */
	protected function compileStatementsCallback(array $match): string
	{
		if (method_exists($this, $method = 'compile' . ucfirst($match[1])))
		{
			$match[0] = $this->$method(array_get($match, 3));
		}

		return isset($match[3]) ? $match[0] : $match[0] . $match[2];
	}

	/**
	 * Compile the "regular" echo statements.
	 *
	 * @param   string  $value
	 *
	 * @return  string
	 */
	protected function compileRegularEchos(string $value): string
	{
		$pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->contentTags[0], $this->contentTags[1]);

		return preg_replace_callback($pattern, [$this, 'compileRegularEchosCallback'], $value);
	}

	/**
	 * Callback for compileRegularEchos, since $this is not allowed in Closures under PHP 5.3.
	 *
	 * @param   array  $matches
	 *
	 * @return  string
	 */
	protected function compileRegularEchosCallback(array $matches): string
	{
		$whitespace = empty($matches[3]) ? '' : $matches[3] . $matches[3];

		return $matches[1] ? substr($matches[0], 1) : '<?php echo ' . $this->compileEchoDefaults($matches[2]) . '; ?>' . $whitespace;
	}

	/**
	 * Compile the escaped echo statements.
	 *
	 * @param   string  $value
	 *
	 * @return string
	 */
	protected function compileEscapedEchos(string $value): string
	{
		$pattern = sprintf('/%s\s*(.+?)\s*%s(\r?\n)?/s', $this->escapedTags[0], $this->escapedTags[1]);

		return preg_replace_callback($pattern, [$this, 'compileEscapedEchosCallback'], $value);
	}

	/**
	 * Callback for compileEscapedEchos, since $this is not allowed in Closures under PHP 5.3.
	 *
	 * @param   array  $matches
	 *
	 * @return  string
	 */
	protected function compileEscapedEchosCallback(array $matches): string
	{
		$whitespace = empty($matches[2]) ? '' : $matches[2] . $matches[2];

		return '<?php echo $this->escape(' . $this->compileEchoDefaults($matches[1]) . '); ?>' . $whitespace;
	}

	/**
	 * Compile the each statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileEach(?string $expression): string
	{
		return "<?php echo \$this->renderEach{$expression}; ?>";
	}

	/**
	 * Compile the yield statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileYield(?string $expression): string
	{
		return "<?php echo \$this->yieldContent{$expression}; ?>";
	}

	/**
	 * Compile the show statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileShow(?string $expression = ''): string
	{
		return "<?php echo \$this->yieldSection(); ?>";
	}

	/**
	 * Compile the section statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileSection(?string $expression): string
	{
		return "<?php \$this->startSection{$expression}; ?>";
	}

	/**
	 * Compile the append statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileAppend(?string $expression): string
	{
		return "<?php \$this->appendSection(); ?>";
	}

	/**
	 * Compile the end-section statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileEndsection(?string $expression): string
	{
		return "<?php \$this->stopSection(); ?>";
	}

	/**
	 * Compile the stop statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileStop(?string $expression): string
	{
		return "<?php \$this->stopSection(); ?>";
	}

	/**
	 * Compile the overwrite statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileOverwrite(?string $expression): string
	{
		return "<?php \$this->stopSection(true); ?>";
	}

	/**
	 * Compile the unless statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileUnless(?string $expression): string
	{
		return "<?php if ( ! $expression): ?>";
	}

	/**
	 * Compile the end unless statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileEndunless(?string $expression): string
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile the end repeatable statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileRepeatable(?string $expression): string
	{
		$expression = trim($expression, '()');
		$parts      = explode(',', $expression, 2);

		$functionName  = '_fof_blade_repeatable_' . md5($this->path . trim($parts[0]));
		$argumentsList = $parts[1] ?? '';

		return "<?php @\$$functionName = function($argumentsList) { ?>";
	}

	/**
	 * Compile the end endRepeatable statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileEndRepeatable(?string $expression): string
	{
		return "<?php }; ?>";
	}

	/**
	 * Compile the end yieldRepeatable statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileYieldRepeatable(?string $expression): string
	{
		$expression = trim($expression, '()');
		$parts      = explode(',', $expression, 2);

		$functionName  = '_fof_blade_repeatable_' . md5($this->path . trim($parts[0]));
		$argumentsList = $parts[1] ?? '';

		return "<?php \$$functionName($argumentsList); ?>";
	}

	/**
	 * Compile the lang statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileLang(?string $expression): string
	{
		return "<?php echo \\Joomla\\CMS\\Language\\Text::_$expression; ?>";
	}

	/**
	 * Compile the sprintf statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileSprintf(?string $expression): string
	{
		return "<?php echo \\Joomla\\CMS\\Language\\Text::sprintf$expression; ?>";
	}

	/**
	 * Compile the plural statements into valid PHP.
	 *
	 * e.g. @plural('COM_FOOBAR_N_ITEMS_SAVED', $countItemsSaved)
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 * @see Text::plural()
	 *
	 */
	protected function compilePlural(?string $expression): string
	{
		return "<?php echo \\Joomla\\CMS\\Language\\Text::plural$expression; ?>";
	}

	/**
	 * Compile the token statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileToken(?string $expression): string
	{
		return "<?php echo \$this->container->platform->getToken(true); ?>";
	}

	/**
	 * Compile the else statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileElse(?string $expression): string
	{
		return "<?php else: ?>";
	}

	/**
	 * Compile the for statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileFor(?string $expression): string
	{
		return "<?php for{$expression}: ?>";
	}

	/**
	 * Compile the foreach statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileForeach(?string $expression): string
	{
		return "<?php foreach{$expression}: ?>";
	}

	/**
	 * Compile the forelse statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileForelse(?string $expression): string
	{
		$empty = '$__empty_' . ++$this->forelseCounter;

		return "<?php {$empty} = true; foreach{$expression}: {$empty} = false; ?>";
	}

	/**
	 * Compile the if statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileIf(?string $expression): string
	{
		return "<?php if{$expression}: ?>";
	}

	/**
	 * Compile the else-if statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileElseif(?string $expression): string
	{
		return "<?php elseif{$expression}: ?>";
	}

	/**
	 * Compile the forelse statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileEmpty(?string $expression): string
	{
		$empty = '$__empty_' . $this->forelseCounter--;

		return "<?php endforeach; if ({$empty}): ?>";
	}

	/**
	 * Compile the while statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileWhile(?string $expression): string
	{
		return "<?php while{$expression}: ?>";
	}

	/**
	 * Compile the end-while statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileEndwhile(?string $expression): string
	{
		return "<?php endwhile; ?>";
	}

	/**
	 * Compile the end-for statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileEndfor(?string $expression): string
	{
		return "<?php endfor; ?>";
	}

	/**
	 * Compile the end-for-each statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileEndforeach(?string $expression): string
	{
		return "<?php endforeach; ?>";
	}

	/**
	 * Compile the end-if statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileEndif(?string $expression): string
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile the end-for-else statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileEndforelse(?string $expression): string
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile the extends statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileExtends(?string $expression): string
	{
		if (starts_with($expression, '('))
		{
			$expression = substr($expression, 1, -1);
		}

		$data = "<?php echo \$this->loadAnyTemplate($expression); ?>";

		$this->footer[] = $data;

		return '';
	}

	/**
	 * Compile the include statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileInclude(?string $expression): string
	{
		if (starts_with($expression, '('))
		{
			$expression = substr($expression, 1, -1);
		}

		return "<?php echo \$this->loadAnyTemplate($expression); ?>";
	}

	/**
	 * Compile the jlayout statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileJlayout(?string $expression): string
	{
		if (starts_with($expression, '('))
		{
			$expression = substr($expression, 1, -1);
		}

		return "<?php echo \\FOF40\\Layout\\LayoutHelper::render(\$this->container, $expression); ?>";
	}

	/**
	 * Compile the stack statements into the content
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileStack(?string $expression): string
	{
		return "<?php echo \$this->yieldContent{$expression}; ?>";
	}

	/**
	 * Compile the push statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compilePush(?string $expression): string
	{
		return "<?php \$this->startSection{$expression}; ?>";
	}

	/**
	 * Compile the endpush statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 */
	protected function compileEndpush(?string $expression): string
	{
		return "<?php \$this->appendSection(); ?>";
	}

	/**
	 * Compile the route statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileRoute(?string $expression): string
	{
		return "<?php echo \$this->container->template->route{$expression}; ?>";
	}

	/**
	 * Compile the css statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileCss(?string $expression): string
	{
		return "<?php \$this->addCssFile{$expression}; ?>";
	}

	/**
	 * Compile the inlineCss statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileInlineCss(?string $expression): string
	{
		return "<?php \$this->addCssInline{$expression}; ?>";
	}

	/**
	 * Compile the inlineJs statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileInlineJs(?string $expression): string
	{
		return "<?php \$this->addJavascriptInline{$expression}; ?>";
	}

	/**
	 * Compile the js statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileJs(?string $expression): string
	{
		return "<?php \$this->addJavascriptFile{$expression}; ?>";
	}

	/**
	 * Compile the jhtml statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileJhtml(?string $expression): string
	{
		return "<?php echo \\Joomla\\CMS\\HTML\\HTMLHelper::_{$expression}; ?>";
	}

	/**
	 * Compile the `sortgrid` statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	protected function compileSortgrid(?string $expression): string
	{
		return "<?php echo \FOF40\Html\FEFHelper\BrowseView::sortGrid{$expression} ?>";
	}

	/**
	 * Compile the `fieldtitle` statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	protected function compileFieldtitle(?string $expression): string
	{
		return "<?php echo \FOF40\Html\FEFHelper\BrowseView::fieldLabel{$expression} ?>";
	}

	/**
	 * Compile the `modelfilter($localField, [$modelTitleField, $modelName, $placeholder, $params])` statements into
	 * valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	protected function compileModelfilter(?string $expression): string
	{
		return "<?php echo \FOF40\Html\FEFHelper\BrowseView::modelFilter{$expression} ?>";
	}

	/**
	 * Compile the `selectfilter($localField, $options [, $placeholder, $params])` statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	protected function compileSelectfilter(?string $expression): string
	{
		return "<?php echo \FOF40\Html\FEFHelper\BrowseView::selectFilter{$expression} ?>";
	}

	/**
	 * Compile the `searchfilter($localField, $searchField = null, $placeholder = null, array $attributes = [])`
	 * statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	protected function compileSearchfilter(?string $expression): string
	{
		return "<?php echo \FOF40\Html\FEFHelper\BrowseView::searchFilter{$expression} ?>";
	}

	/**
	 * Compile the media statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileMedia(?string $expression): string
	{
		return "<?php echo \$this->container->template->parsePath{$expression}; ?>";
	}

	/**
	 * Compile the modules statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileModules(?string $expression): string
	{
		return "<?php echo \$this->container->template->loadPosition{$expression}; ?>";
	}

	/**
	 * Compile the module statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileModule(?string $expression): string
	{
		return "<?php echo \$this->container->template->loadModule{$expression}; ?>";
	}

	/**
	 * Compile the editor statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return string
	 */
	protected function compileEditor(?string $expression): string
	{
		return '<?php echo \\Joomla\\CMS\\Editor\\Editor::getInstance($this->container->platform->getConfig()->get(\'editor\', \'tinymce\'))' .
			'->display' . $expression . '; ?>';
	}

	/**
	 * Gets the tags used for the compiler.
	 *
	 * @param   bool  $escaped
	 *
	 * @return array
	 */
	protected function getTags(bool $escaped = false): array
	{
		$tags = $escaped ? $this->escapedTags : $this->contentTags;

		return array_map('stripcslashes', $tags);
	}

}
