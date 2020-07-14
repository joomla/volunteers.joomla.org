<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Copyright (c) 2009, RealDolmen
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of RealDolmen nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY RealDolmen ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL RealDolmen BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Microsoft
 * @package    Microsoft
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */

namespace Akeeba\Engine\Postproc\Connector\Azure;



use Akeeba\Engine\Postproc\Connector\Azure\Retrypolicy\None;
use Akeeba\Engine\Postproc\Connector\Azure\Retrypolicy\Ntimes;

/**
 * @category   Microsoft
 * @package    Microsoft_WindowsAzure
 * @subpackage RetryPolicy
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
abstract class Retrypolicy
{
	/**
	 * Create a None instance
	 *
	 * @return None
	 */
	public static function noRetry()
	{
		return new None();
	}

	/**
	 * Create a Ntimes instance
	 *
	 * @param   int  $count                   Number of retries
	 * @param   int  $intervalBetweenRetries  Interval between retries (in milliseconds)
	 *
	 * @return Ntimes
	 */
	public static function retryN($count = 1, $intervalBetweenRetries = 0)
	{
		return new Ntimes($count, $intervalBetweenRetries);
	}

	/**
	 * Execute function under retry policy
	 *
	 * @param   string|array  $function    Function to execute
	 * @param   array         $parameters  Parameters for function call
	 *
	 * @return mixed
	 */
	public abstract function execute($function, $parameters = []);
}
