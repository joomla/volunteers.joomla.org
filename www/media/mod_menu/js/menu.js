function _extends() {
  return _extends = Object.assign ? Object.assign.bind() : function (n) {
    for (var e = 1; e < arguments.length; e++) {
      var t = arguments[e];
      for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]);
    }
    return n;
  }, _extends.apply(null, arguments);
}

/**
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

(() => {

  /**
   * Navigation menu
   *
   * Example usage:
   *   // Default behavior (uses menuHoverClass = 'show-menu', dir = 'ltr')
   *   new Nav(document.querySelector('.nav'));
   *
   *   // Override defaults (e.g. custom open-class and RTL support)
   *   new Nav(document.querySelector('.nav'), {
   *     menuHoverClass: 'my-open-class',
   *     dir:            'rtl'
   *   });
   *
   * @param {HTMLElement} nav                                   The root <ul class="nav"> element
   * @param {Object}      [settings]                            Optional overrides for defaultSettings
   * @param {string}      [settings.menuHoverClass='show-menu'] CSS class to toggle on open submenus
   * @param {string}      [settings.dir='ltr']                  Text direction for keyboard nav ('ltr'|'rtl')
   */
  class Nav {
    constructor(nav, settings = {}) {
      var _settings$dir, _this$nav$id, _this$nav;
      this.nav = nav;

      // read the HTML dir attribute or computed style, or fall back to defaultSettings.dir
      const browserDir = document.documentElement.getAttribute('dir') // <html dir="…">
      || getComputedStyle(document.documentElement).direction // CSS direction
      || Nav.defaultSettings.dir;
      this.settings = _extends({}, Nav.defaultSettings, settings);

      // merge defaults, browser‐detected dir, and any explicit overrides in `settings`
      this.settings = _extends({}, Nav.defaultSettings, {
        dir: (_settings$dir = settings.dir) != null ? _settings$dir : browserDir
      }, settings);

      // Unique prefix for this nav instance - needed for the id of submenus and aria-controls
      this.idPrefix = (_this$nav$id = (_this$nav = this.nav) == null ? void 0 : _this$nav.id) != null ? _this$nav$id : `nav-${Math.floor(Math.random() * 100000)}`;
      this.topLevelNodes = this.nav.querySelectorAll(':scope > li');
      this.nav.querySelectorAll('li').forEach(menuItem => {
        // only direct submenu(s) of each menu item
        const levelChildUls = menuItem.querySelectorAll(':scope > ul');
        const ariaControls = [];
        levelChildUls.forEach(childUl => {
          childUl.setAttribute('aria-hidden', 'true');
          childUl.classList.remove(this.settings.menuHoverClass);
          childUl.id = `${this.idPrefix}-submenu${Nav.idCounter}`;
          Nav.idCounter += 1;
          ariaControls.push(childUl.id);
        });
        if (levelChildUls.length > 0) {
          const togglebtn = menuItem.querySelector(':scope > [aria-expanded]');
          togglebtn == null || togglebtn.setAttribute('aria-controls', ariaControls.join(' '));
          togglebtn == null || togglebtn.setAttribute('aria-expanded', 'false');
        }
      });
      nav.addEventListener('keydown', this.onMenuKeyDown.bind(this));
      nav.addEventListener('click', this.onClick.bind(this));
      if (this.nav.classList.contains(this.settings.preventSubmenuOpenOnload)) {
        this.toggleAllForCurrentActive();
      }
    }
    onMenuKeyDown(event) {
      const target = event.target.closest('li');
      if (!target) {
        return;
      }
      const subLists = target.querySelectorAll(':scope > ul');
      switch (event.key) {
        case 'ArrowUp':
          event.preventDefault();
          this.tabPrev();
          break;
        case 'ArrowLeft':
          event.preventDefault();
          if (this.settings.dir === 'rtl') {
            this.tabNext();
          } else {
            this.tabPrev();
          }
          break;
        case 'ArrowDown':
          event.preventDefault();
          this.tabNext();
          break;
        case 'ArrowRight':
          event.preventDefault();
          if (this.settings.dir === 'rtl') {
            this.tabPrev();
          } else {
            this.tabNext();
          }
          break;
        case 'Enter':
          if (event.target.nodeName === 'SPAN' && event.target.parentNode.nodeName !== 'A' && subLists.length > 0) {
            var _subLists$;
            event.preventDefault();
            this.toggleSubMenu(target, subLists, ((_subLists$ = subLists[0]) == null ? void 0 : _subLists$.getAttribute('aria-hidden')) === 'true');
          }
          break;
        case ' ':
        case 'Spacebar':
          if (subLists.length > 0) {
            var _subLists$2;
            event.preventDefault();
            this.toggleSubMenu(target, subLists, ((_subLists$2 = subLists[0]) == null ? void 0 : _subLists$2.getAttribute('aria-hidden')) === 'true');
          }
          break;
        case 'Escape':
          {
            event.preventDefault();
            const currentTopLevelLi = this.getTopLevelParentLi(event.target);
            if (!currentTopLevelLi) {
              break;
            }
            const allChildListsFromTopLevelLi = currentTopLevelLi.querySelectorAll(':scope > ul');
            if (allChildListsFromTopLevelLi.length > 0) {
              this.toggleSubMenu(currentTopLevelLi, allChildListsFromTopLevelLi, false);
            }
            // set focus on the top level li child with tabindex
            currentTopLevelLi.querySelectorAll(':scope > [tabindex]:not([tabindex="-1"]), a, button').forEach(tabElement => {
              if (tabElement.hasAttribute(['aria-expanded'])) {
                tabElement.focus();
              }
            });
            break;
          }
        case 'End':
          {
            var _target$closest;
            event.preventDefault();
            const currentLiList = (_target$closest = target.closest('ul')) == null ? void 0 : _target$closest.querySelectorAll(':scope > li');
            for (let index = currentLiList.length - 1; index >= 0; index -= 1) {
              const lastTabbable = currentLiList[index].querySelector(':scope > [tabindex]:not([tabindex="-1"]), a, button');
              if (lastTabbable) {
                lastTabbable.focus();
                return;
              }
            }
            break;
          }
        case 'Home':
          {
            var _target$closest2;
            event.preventDefault();
            const firstLi = (_target$closest2 = target.closest('ul')) == null ? void 0 : _target$closest2.querySelector(':scope > li:first-child');
            if (firstLi) {
              var _firstLi$querySelecto;
              // set focus on first li child with tabindex within current list
              (_firstLi$querySelecto = firstLi.querySelector(':scope > [tabindex]:not([tabindex="-1"]), a, button')) == null || _firstLi$querySelecto.focus();
            }
            break;
          }
      }
    }
    onClick(event) {
      var _event$target, _event$target2, _event$target3, _event$target4;
      if (!((_event$target = event.target) != null && _event$target.hasAttribute('aria-expanded')) && !((_event$target2 = event.target) != null && _event$target2.closest('[aria-expanded'))) {
        return;
      }
      if (((_event$target3 = event.target) == null ? void 0 : _event$target3.nodeName) === 'A') {
        return;
      }
      if (((_event$target4 = event.target) == null ? void 0 : _event$target4.nodeName) === 'SPAN' && event.target.parentNode.nodeName === 'A') {
        return;
      }
      const target = event.target.closest('li');
      const subLists = target == null ? void 0 : target.querySelectorAll(':scope > ul');
      if (subLists && subLists.length > 0) {
        var _subLists$3;
        event.preventDefault();
        this.toggleSubMenu(target, subLists, ((_subLists$3 = subLists[0]) == null ? void 0 : _subLists$3.getAttribute('aria-hidden')) === 'true');
      }
    }
    toggleSubMenu(target, subLists, open = false) {
      var _target$querySelector;
      const closeSubMenuTree = ulChild => {
        var _ulChild$closest;
        ulChild.setAttribute('aria-hidden', 'true');
        ulChild.classList.remove(this.settings.menuHoverClass);
        const parentToggle = (_ulChild$closest = ulChild.closest('li')) == null ? void 0 : _ulChild$closest.querySelector(':scope > [aria-expanded]');
        parentToggle == null || parentToggle.setAttribute('aria-expanded', 'false');
        ulChild.querySelectorAll('ul').forEach(descendantUl => {
          var _descendantUl$closest;
          descendantUl.setAttribute('aria-hidden', 'true');
          descendantUl.classList.remove(this.settings.menuHoverClass);
          (_descendantUl$closest = descendantUl.closest('li')) == null || (_descendantUl$closest = _descendantUl$closest.querySelector(':scope > [aria-expanded]')) == null || _descendantUl$closest.setAttribute('aria-expanded', 'false');
        });
      };
      if (open) {
        // close only sibling branches at the same level, keep ancestor branch open
        const parentUl = target == null ? void 0 : target.closest('ul');
        const siblingItems = parentUl == null ? void 0 : parentUl.querySelectorAll(':scope > li');
        siblingItems == null || siblingItems.forEach(siblingItem => {
          if (siblingItem === target) {
            return;
          }
          siblingItem.querySelectorAll(':scope > ul').forEach(ulChild => {
            closeSubMenuTree(ulChild);
          });
        });
      }
      if (open) {
        subLists.forEach(ulChild => {
          ulChild.setAttribute('aria-hidden', 'false');
          ulChild.classList.add(this.settings.menuHoverClass);
        });
      } else {
        subLists.forEach(closeSubMenuTree);
      }
      (_target$querySelector = target.querySelector(':scope > [aria-expanded]')) == null || _target$querySelector.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    focusTabbable(direction = 1) {
      const tabbables = Array.from(this.nav.querySelectorAll('[tabindex]:not([tabindex="-1"]), a, button')).filter(el => !el.disabled && el.tabIndex >= 0 && el.offsetParent !== null);
      const currentIndex = tabbables.indexOf(document.activeElement);
      if (tabbables.length === 0) return;
      const nextIndex = (currentIndex + direction + tabbables.length) % tabbables.length;
      tabbables[nextIndex].focus();
    }
    tabNext() {
      this.focusTabbable(1);
    }
    tabPrev() {
      this.focusTabbable(-1);
    }
    getTopLevelParentLi(element) {
      let currentLi = element.closest('li');
      // this.topLevelNodes is a NodeList of top-level li elements in this nav
      while (currentLi && !Array.from(this.topLevelNodes).includes(currentLi)) {
        const parentUl = currentLi.parentElement.closest('ul');
        currentLi = parentUl ? parentUl.closest('li') : null;
      }
      return currentLi; // top-level li or null if not found, or the
    }
    toggleAllForCurrentActive() {
      const active = this.nav.querySelector('.current.active');
      if (active) {
        let currentLi = active;
        while (currentLi && !Array.from(this.topLevelNodes).includes(currentLi)) {
          const parentUl = currentLi.parentElement.closest('ul');
          currentLi = parentUl ? parentUl.closest('li') : null;
          if (currentLi) {
            var _subLists$4;
            const subLists = currentLi.querySelectorAll(':scope > ul');
            this.toggleSubMenu(currentLi, subLists, ((_subLists$4 = subLists[0]) == null ? void 0 : _subLists$4.getAttribute('aria-hidden')) === 'true');
          }
        }
      }
    }
  }

  // static idCounter for unique id generation of submenus
  // Default settings for the Nav class
  Nav.defaultSettings = {
    menuHoverClass: 'show-menu',
    dir: 'ltr',
    preventSubmenuOpenOnload: 'nav-active-open'
  };
  Nav.idCounter = 0;

  // Initialize Nav instances for all nav elements on the page
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.nav').forEach(nav => new Nav(nav));
  });
})();
