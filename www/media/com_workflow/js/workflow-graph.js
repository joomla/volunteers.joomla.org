import JoomlaDialog from 'joomla.dialog';

/**
* @vue/shared v3.5.30
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
// @__NO_SIDE_EFFECTS__
function makeMap(str) {
  const map = /* @__PURE__ */Object.create(null);
  for (const key of str.split(",")) map[key] = 1;
  return val => val in map;
}
const EMPTY_OBJ = {};
const EMPTY_ARR = [];
const NOOP = () => {};
const NO = () => false;
const isOn = key => key.charCodeAt(0) === 111 && key.charCodeAt(1) === 110 && (
// uppercase letter
key.charCodeAt(2) > 122 || key.charCodeAt(2) < 97);
const isModelListener = key => key.startsWith("onUpdate:");
const extend$2 = Object.assign;
const remove$2 = (arr, el) => {
  const i = arr.indexOf(el);
  if (i > -1) {
    arr.splice(i, 1);
  }
};
const hasOwnProperty$1 = Object.prototype.hasOwnProperty;
const hasOwn = (val, key) => hasOwnProperty$1.call(val, key);
const isArray = Array.isArray;
const isMap = val => toTypeString(val) === "[object Map]";
const isSet = val => toTypeString(val) === "[object Set]";
const isDate = val => toTypeString(val) === "[object Date]";
const isFunction = val => typeof val === "function";
const isString = val => typeof val === "string";
const isSymbol = val => typeof val === "symbol";
const isObject$2 = val => val !== null && typeof val === "object";
const isPromise$1 = val => {
  return (isObject$2(val) || isFunction(val)) && isFunction(val.then) && isFunction(val.catch);
};
const objectToString = Object.prototype.toString;
const toTypeString = value => objectToString.call(value);
const toRawType = value => {
  return toTypeString(value).slice(8, -1);
};
const isPlainObject = val => toTypeString(val) === "[object Object]";
const isIntegerKey = key => isString(key) && key !== "NaN" && key[0] !== "-" && "" + parseInt(key, 10) === key;
const isReservedProp = /* @__PURE__ */makeMap(
// the leading comma is intentional so empty string "" is also included
",key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted");
const cacheStringFunction = fn => {
  const cache = /* @__PURE__ */Object.create(null);
  return str => {
    const hit = cache[str];
    return hit || (cache[str] = fn(str));
  };
};
const camelizeRE = /-\w/g;
const camelize = cacheStringFunction(str => {
  return str.replace(camelizeRE, c => c.slice(1).toUpperCase());
});
const hyphenateRE = /\B([A-Z])/g;
const hyphenate = cacheStringFunction(str => str.replace(hyphenateRE, "-$1").toLowerCase());
const capitalize = cacheStringFunction(str => {
  return str.charAt(0).toUpperCase() + str.slice(1);
});
const toHandlerKey$1 = cacheStringFunction(str => {
  const s = str ? "on" + capitalize(str) : "";
  return s;
});
const hasChanged = (value, oldValue) => !Object.is(value, oldValue);
const invokeArrayFns = function invokeArrayFns(fns) {
  for (var _len = arguments.length, arg = new Array(_len > 1 ? _len - 1 : 0), _key2 = 1; _key2 < _len; _key2++) {
    arg[_key2 - 1] = arguments[_key2];
  }
  for (let i = 0; i < fns.length; i++) {
    fns[i](...arg);
  }
};
const def = function def(obj, key, value, writable) {
  if (writable === void 0) {
    writable = false;
  }
  Object.defineProperty(obj, key, {
    configurable: true,
    enumerable: false,
    writable,
    value
  });
};
const looseToNumber = val => {
  const n = parseFloat(val);
  return isNaN(n) ? val : n;
};
let _globalThis;
const getGlobalThis = () => {
  return _globalThis || (_globalThis = typeof globalThis !== "undefined" ? globalThis : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : typeof global !== "undefined" ? global : {});
};
function normalizeStyle(value) {
  if (isArray(value)) {
    const res = {};
    for (let i = 0; i < value.length; i++) {
      const item = value[i];
      const normalized = isString(item) ? parseStringStyle(item) : normalizeStyle(item);
      if (normalized) {
        for (const key in normalized) {
          res[key] = normalized[key];
        }
      }
    }
    return res;
  } else if (isString(value) || isObject$2(value)) {
    return value;
  }
}
const listDelimiterRE = /;(?![^(]*\))/g;
const propertyDelimiterRE = /:([^]+)/;
const styleCommentRE = /\/\*[^]*?\*\//g;
function parseStringStyle(cssText) {
  const ret = {};
  cssText.replace(styleCommentRE, "").split(listDelimiterRE).forEach(item => {
    if (item) {
      const tmp = item.split(propertyDelimiterRE);
      tmp.length > 1 && (ret[tmp[0].trim()] = tmp[1].trim());
    }
  });
  return ret;
}
function normalizeClass(value) {
  let res = "";
  if (isString(value)) {
    res = value;
  } else if (isArray(value)) {
    for (let i = 0; i < value.length; i++) {
      const normalized = normalizeClass(value[i]);
      if (normalized) {
        res += normalized + " ";
      }
    }
  } else if (isObject$2(value)) {
    for (const name in value) {
      if (value[name]) {
        res += name + " ";
      }
    }
  }
  return res.trim();
}
function normalizeProps(props) {
  if (!props) return null;
  let klass = props.class,
    style = props.style;
  if (klass && !isString(klass)) {
    props.class = normalizeClass(klass);
  }
  if (style) {
    props.style = normalizeStyle(style);
  }
  return props;
}
const specialBooleanAttrs = "itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly";
const isSpecialBooleanAttr = /* @__PURE__ */makeMap(specialBooleanAttrs);
function includeBooleanAttr(value) {
  return !!value || value === "";
}
function looseCompareArrays(a, b) {
  if (a.length !== b.length) return false;
  let equal = true;
  for (let i = 0; equal && i < a.length; i++) {
    equal = looseEqual(a[i], b[i]);
  }
  return equal;
}
function looseEqual(a, b) {
  if (a === b) return true;
  let aValidType = isDate(a);
  let bValidType = isDate(b);
  if (aValidType || bValidType) {
    return aValidType && bValidType ? a.getTime() === b.getTime() : false;
  }
  aValidType = isSymbol(a);
  bValidType = isSymbol(b);
  if (aValidType || bValidType) {
    return a === b;
  }
  aValidType = isArray(a);
  bValidType = isArray(b);
  if (aValidType || bValidType) {
    return aValidType && bValidType ? looseCompareArrays(a, b) : false;
  }
  aValidType = isObject$2(a);
  bValidType = isObject$2(b);
  if (aValidType || bValidType) {
    if (!aValidType || !bValidType) {
      return false;
    }
    const aKeysCount = Object.keys(a).length;
    const bKeysCount = Object.keys(b).length;
    if (aKeysCount !== bKeysCount) {
      return false;
    }
    for (const key in a) {
      const aHasKey = a.hasOwnProperty(key);
      const bHasKey = b.hasOwnProperty(key);
      if (aHasKey && !bHasKey || !aHasKey && bHasKey || !looseEqual(a[key], b[key])) {
        return false;
      }
    }
  }
  return String(a) === String(b);
}
const isRef$1 = val => {
  return !!(val && val["__v_isRef"] === true);
};
const toDisplayString = val => {
  return isString(val) ? val : val == null ? "" : isArray(val) || isObject$2(val) && (val.toString === objectToString || !isFunction(val.toString)) ? isRef$1(val) ? toDisplayString(val.value) : JSON.stringify(val, replacer, 2) : String(val);
};
const replacer = (_key, val) => {
  if (isRef$1(val)) {
    return replacer(_key, val.value);
  } else if (isMap(val)) {
    return {
      ["Map(" + val.size + ")"]: [...val.entries()].reduce((entries, _ref, i) => {
        let key = _ref[0],
          val2 = _ref[1];
        entries[stringifySymbol(key, i) + " =>"] = val2;
        return entries;
      }, {})
    };
  } else if (isSet(val)) {
    return {
      ["Set(" + val.size + ")"]: [...val.values()].map(v => stringifySymbol(v))
    };
  } else if (isSymbol(val)) {
    return stringifySymbol(val);
  } else if (isObject$2(val) && !isArray(val) && !isPlainObject(val)) {
    return String(val);
  }
  return val;
};
const stringifySymbol = function stringifySymbol(v, i) {
  if (i === void 0) {
    i = "";
  }
  var _a;
  return (
    // Symbol.description in es2019+ so we need to cast here to pass
    // the lib: es2016 check
    isSymbol(v) ? "Symbol(" + ((_a = v.description) != null ? _a : i) + ")" : v
  );
};

/**
* @vue/reactivity v3.5.30
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
let activeEffectScope;
class EffectScope {
  // TODO isolatedDeclarations "__v_skip"
  constructor(detached) {
    if (detached === void 0) {
      detached = false;
    }
    this.detached = detached;
    /**
     * @internal
     */
    this._active = true;
    /**
     * @internal track `on` calls, allow `on` call multiple times
     */
    this._on = 0;
    /**
     * @internal
     */
    this.effects = [];
    /**
     * @internal
     */
    this.cleanups = [];
    this._isPaused = false;
    this.__v_skip = true;
    this.parent = activeEffectScope;
    if (!detached && activeEffectScope) {
      this.index = (activeEffectScope.scopes || (activeEffectScope.scopes = [])).push(this) - 1;
    }
  }
  get active() {
    return this._active;
  }
  pause() {
    if (this._active) {
      this._isPaused = true;
      let i, l;
      if (this.scopes) {
        for (i = 0, l = this.scopes.length; i < l; i++) {
          this.scopes[i].pause();
        }
      }
      for (i = 0, l = this.effects.length; i < l; i++) {
        this.effects[i].pause();
      }
    }
  }
  /**
   * Resumes the effect scope, including all child scopes and effects.
   */
  resume() {
    if (this._active) {
      if (this._isPaused) {
        this._isPaused = false;
        let i, l;
        if (this.scopes) {
          for (i = 0, l = this.scopes.length; i < l; i++) {
            this.scopes[i].resume();
          }
        }
        for (i = 0, l = this.effects.length; i < l; i++) {
          this.effects[i].resume();
        }
      }
    }
  }
  run(fn) {
    if (this._active) {
      const currentEffectScope = activeEffectScope;
      try {
        activeEffectScope = this;
        return fn();
      } finally {
        activeEffectScope = currentEffectScope;
      }
    }
  }
  /**
   * This should only be called on non-detached scopes
   * @internal
   */
  on() {
    if (++this._on === 1) {
      this.prevScope = activeEffectScope;
      activeEffectScope = this;
    }
  }
  /**
   * This should only be called on non-detached scopes
   * @internal
   */
  off() {
    if (this._on > 0 && --this._on === 0) {
      activeEffectScope = this.prevScope;
      this.prevScope = void 0;
    }
  }
  stop(fromParent) {
    if (this._active) {
      this._active = false;
      let i, l;
      for (i = 0, l = this.effects.length; i < l; i++) {
        this.effects[i].stop();
      }
      this.effects.length = 0;
      for (i = 0, l = this.cleanups.length; i < l; i++) {
        this.cleanups[i]();
      }
      this.cleanups.length = 0;
      if (this.scopes) {
        for (i = 0, l = this.scopes.length; i < l; i++) {
          this.scopes[i].stop(true);
        }
        this.scopes.length = 0;
      }
      if (!this.detached && this.parent && !fromParent) {
        const last = this.parent.scopes.pop();
        if (last && last !== this) {
          this.parent.scopes[this.index] = last;
          last.index = this.index;
        }
      }
      this.parent = void 0;
    }
  }
}
function effectScope(detached) {
  return new EffectScope(detached);
}
function getCurrentScope() {
  return activeEffectScope;
}
function onScopeDispose(fn, failSilently) {
  if (activeEffectScope) {
    activeEffectScope.cleanups.push(fn);
  }
}
let activeSub;
const pausedQueueEffects = /* @__PURE__ */new WeakSet();
class ReactiveEffect {
  constructor(fn) {
    this.fn = fn;
    /**
     * @internal
     */
    this.deps = void 0;
    /**
     * @internal
     */
    this.depsTail = void 0;
    /**
     * @internal
     */
    this.flags = 1 | 4;
    /**
     * @internal
     */
    this.next = void 0;
    /**
     * @internal
     */
    this.cleanup = void 0;
    this.scheduler = void 0;
    if (activeEffectScope && activeEffectScope.active) {
      activeEffectScope.effects.push(this);
    }
  }
  pause() {
    this.flags |= 64;
  }
  resume() {
    if (this.flags & 64) {
      this.flags &= -65;
      if (pausedQueueEffects.has(this)) {
        pausedQueueEffects.delete(this);
        this.trigger();
      }
    }
  }
  /**
   * @internal
   */
  notify() {
    if (this.flags & 2 && !(this.flags & 32)) {
      return;
    }
    if (!(this.flags & 8)) {
      batch(this);
    }
  }
  run() {
    if (!(this.flags & 1)) {
      return this.fn();
    }
    this.flags |= 2;
    cleanupEffect(this);
    prepareDeps(this);
    const prevEffect = activeSub;
    const prevShouldTrack = shouldTrack;
    activeSub = this;
    shouldTrack = true;
    try {
      return this.fn();
    } finally {
      cleanupDeps(this);
      activeSub = prevEffect;
      shouldTrack = prevShouldTrack;
      this.flags &= -3;
    }
  }
  stop() {
    if (this.flags & 1) {
      for (let link = this.deps; link; link = link.nextDep) {
        removeSub(link);
      }
      this.deps = this.depsTail = void 0;
      cleanupEffect(this);
      this.onStop && this.onStop();
      this.flags &= -2;
    }
  }
  trigger() {
    if (this.flags & 64) {
      pausedQueueEffects.add(this);
    } else if (this.scheduler) {
      this.scheduler();
    } else {
      this.runIfDirty();
    }
  }
  /**
   * @internal
   */
  runIfDirty() {
    if (isDirty(this)) {
      this.run();
    }
  }
  get dirty() {
    return isDirty(this);
  }
}
let batchDepth = 0;
let batchedSub;
let batchedComputed;
function batch(sub, isComputed) {
  if (isComputed === void 0) {
    isComputed = false;
  }
  sub.flags |= 8;
  if (isComputed) {
    sub.next = batchedComputed;
    batchedComputed = sub;
    return;
  }
  sub.next = batchedSub;
  batchedSub = sub;
}
function startBatch() {
  batchDepth++;
}
function endBatch() {
  if (--batchDepth > 0) {
    return;
  }
  if (batchedComputed) {
    let e = batchedComputed;
    batchedComputed = void 0;
    while (e) {
      const next = e.next;
      e.next = void 0;
      e.flags &= -9;
      e = next;
    }
  }
  let error;
  while (batchedSub) {
    let e = batchedSub;
    batchedSub = void 0;
    while (e) {
      const next = e.next;
      e.next = void 0;
      e.flags &= -9;
      if (e.flags & 1) {
        try {
          ;
          e.trigger();
        } catch (err) {
          if (!error) error = err;
        }
      }
      e = next;
    }
  }
  if (error) throw error;
}
function prepareDeps(sub) {
  for (let link = sub.deps; link; link = link.nextDep) {
    link.version = -1;
    link.prevActiveLink = link.dep.activeLink;
    link.dep.activeLink = link;
  }
}
function cleanupDeps(sub) {
  let head;
  let tail = sub.depsTail;
  let link = tail;
  while (link) {
    const prev = link.prevDep;
    if (link.version === -1) {
      if (link === tail) tail = prev;
      removeSub(link);
      removeDep(link);
    } else {
      head = link;
    }
    link.dep.activeLink = link.prevActiveLink;
    link.prevActiveLink = void 0;
    link = prev;
  }
  sub.deps = head;
  sub.depsTail = tail;
}
function isDirty(sub) {
  for (let link = sub.deps; link; link = link.nextDep) {
    if (link.dep.version !== link.version || link.dep.computed && (refreshComputed(link.dep.computed) || link.dep.version !== link.version)) {
      return true;
    }
  }
  if (sub._dirty) {
    return true;
  }
  return false;
}
function refreshComputed(computed) {
  if (computed.flags & 4 && !(computed.flags & 16)) {
    return;
  }
  computed.flags &= -17;
  if (computed.globalVersion === globalVersion) {
    return;
  }
  computed.globalVersion = globalVersion;
  if (!computed.isSSR && computed.flags & 128 && (!computed.deps && !computed._dirty || !isDirty(computed))) {
    return;
  }
  computed.flags |= 2;
  const dep = computed.dep;
  const prevSub = activeSub;
  const prevShouldTrack = shouldTrack;
  activeSub = computed;
  shouldTrack = true;
  try {
    prepareDeps(computed);
    const value = computed.fn(computed._value);
    if (dep.version === 0 || hasChanged(value, computed._value)) {
      computed.flags |= 128;
      computed._value = value;
      dep.version++;
    }
  } catch (err) {
    dep.version++;
    throw err;
  } finally {
    activeSub = prevSub;
    shouldTrack = prevShouldTrack;
    cleanupDeps(computed);
    computed.flags &= -3;
  }
}
function removeSub(link, soft) {
  if (soft === void 0) {
    soft = false;
  }
  const dep = link.dep,
    prevSub = link.prevSub,
    nextSub = link.nextSub;
  if (prevSub) {
    prevSub.nextSub = nextSub;
    link.prevSub = void 0;
  }
  if (nextSub) {
    nextSub.prevSub = prevSub;
    link.nextSub = void 0;
  }
  if (dep.subs === link) {
    dep.subs = prevSub;
    if (!prevSub && dep.computed) {
      dep.computed.flags &= -5;
      for (let l = dep.computed.deps; l; l = l.nextDep) {
        removeSub(l, true);
      }
    }
  }
  if (!soft && ! --dep.sc && dep.map) {
    dep.map.delete(dep.key);
  }
}
function removeDep(link) {
  const prevDep = link.prevDep,
    nextDep = link.nextDep;
  if (prevDep) {
    prevDep.nextDep = nextDep;
    link.prevDep = void 0;
  }
  if (nextDep) {
    nextDep.prevDep = prevDep;
    link.nextDep = void 0;
  }
}
let shouldTrack = true;
const trackStack = [];
function pauseTracking() {
  trackStack.push(shouldTrack);
  shouldTrack = false;
}
function resetTracking() {
  const last = trackStack.pop();
  shouldTrack = last === void 0 ? true : last;
}
function cleanupEffect(e) {
  const cleanup = e.cleanup;
  e.cleanup = void 0;
  if (cleanup) {
    const prevSub = activeSub;
    activeSub = void 0;
    try {
      cleanup();
    } finally {
      activeSub = prevSub;
    }
  }
}
let globalVersion = 0;
class Link {
  constructor(sub, dep) {
    this.sub = sub;
    this.dep = dep;
    this.version = dep.version;
    this.nextDep = this.prevDep = this.nextSub = this.prevSub = this.prevActiveLink = void 0;
  }
}
class Dep {
  // TODO isolatedDeclarations "__v_skip"
  constructor(computed) {
    this.computed = computed;
    this.version = 0;
    /**
     * Link between this dep and the current active effect
     */
    this.activeLink = void 0;
    /**
     * Doubly linked list representing the subscribing effects (tail)
     */
    this.subs = void 0;
    /**
     * For object property deps cleanup
     */
    this.map = void 0;
    this.key = void 0;
    /**
     * Subscriber counter
     */
    this.sc = 0;
    /**
     * @internal
     */
    this.__v_skip = true;
  }
  track(debugInfo) {
    if (!activeSub || !shouldTrack || activeSub === this.computed) {
      return;
    }
    let link = this.activeLink;
    if (link === void 0 || link.sub !== activeSub) {
      link = this.activeLink = new Link(activeSub, this);
      if (!activeSub.deps) {
        activeSub.deps = activeSub.depsTail = link;
      } else {
        link.prevDep = activeSub.depsTail;
        activeSub.depsTail.nextDep = link;
        activeSub.depsTail = link;
      }
      addSub(link);
    } else if (link.version === -1) {
      link.version = this.version;
      if (link.nextDep) {
        const next = link.nextDep;
        next.prevDep = link.prevDep;
        if (link.prevDep) {
          link.prevDep.nextDep = next;
        }
        link.prevDep = activeSub.depsTail;
        link.nextDep = void 0;
        activeSub.depsTail.nextDep = link;
        activeSub.depsTail = link;
        if (activeSub.deps === link) {
          activeSub.deps = next;
        }
      }
    }
    return link;
  }
  trigger(debugInfo) {
    this.version++;
    globalVersion++;
    this.notify(debugInfo);
  }
  notify(debugInfo) {
    startBatch();
    try {
      if (!!("production" !== "production")) ;
      for (let link = this.subs; link; link = link.prevSub) {
        if (link.sub.notify()) {
          ;
          link.sub.dep.notify();
        }
      }
    } finally {
      endBatch();
    }
  }
}
function addSub(link) {
  link.dep.sc++;
  if (link.sub.flags & 4) {
    const computed = link.dep.computed;
    if (computed && !link.dep.subs) {
      computed.flags |= 4 | 16;
      for (let l = computed.deps; l; l = l.nextDep) {
        addSub(l);
      }
    }
    const currentTail = link.dep.subs;
    if (currentTail !== link) {
      link.prevSub = currentTail;
      if (currentTail) currentTail.nextSub = link;
    }
    link.dep.subs = link;
  }
}
const targetMap = /* @__PURE__ */new WeakMap();
const ITERATE_KEY = /* @__PURE__ */Symbol("");
const MAP_KEY_ITERATE_KEY = /* @__PURE__ */Symbol("");
const ARRAY_ITERATE_KEY = /* @__PURE__ */Symbol("");
function track(target, type, key) {
  if (shouldTrack && activeSub) {
    let depsMap = targetMap.get(target);
    if (!depsMap) {
      targetMap.set(target, depsMap = /* @__PURE__ */new Map());
    }
    let dep = depsMap.get(key);
    if (!dep) {
      depsMap.set(key, dep = new Dep());
      dep.map = depsMap;
      dep.key = key;
    }
    {
      dep.track();
    }
  }
}
function trigger(target, type, key, newValue, oldValue, oldTarget) {
  const depsMap = targetMap.get(target);
  if (!depsMap) {
    globalVersion++;
    return;
  }
  const run = dep => {
    if (dep) {
      {
        dep.trigger();
      }
    }
  };
  startBatch();
  if (type === "clear") {
    depsMap.forEach(run);
  } else {
    const targetIsArray = isArray(target);
    const isArrayIndex = targetIsArray && isIntegerKey(key);
    if (targetIsArray && key === "length") {
      const newLength = Number(newValue);
      depsMap.forEach((dep, key2) => {
        if (key2 === "length" || key2 === ARRAY_ITERATE_KEY || !isSymbol(key2) && key2 >= newLength) {
          run(dep);
        }
      });
    } else {
      if (key !== void 0 || depsMap.has(void 0)) {
        run(depsMap.get(key));
      }
      if (isArrayIndex) {
        run(depsMap.get(ARRAY_ITERATE_KEY));
      }
      switch (type) {
        case "add":
          if (!targetIsArray) {
            run(depsMap.get(ITERATE_KEY));
            if (isMap(target)) {
              run(depsMap.get(MAP_KEY_ITERATE_KEY));
            }
          } else if (isArrayIndex) {
            run(depsMap.get("length"));
          }
          break;
        case "delete":
          if (!targetIsArray) {
            run(depsMap.get(ITERATE_KEY));
            if (isMap(target)) {
              run(depsMap.get(MAP_KEY_ITERATE_KEY));
            }
          }
          break;
        case "set":
          if (isMap(target)) {
            run(depsMap.get(ITERATE_KEY));
          }
          break;
      }
    }
  }
  endBatch();
}
function getDepFromReactive(object, key) {
  const depMap = targetMap.get(object);
  return depMap && depMap.get(key);
}
function reactiveReadArray(array) {
  const raw = toRaw(array);
  if (raw === array) return raw;
  track(raw, "iterate", ARRAY_ITERATE_KEY);
  return isShallow(array) ? raw : raw.map(toReactive);
}
function shallowReadArray(arr) {
  track(arr = toRaw(arr), "iterate", ARRAY_ITERATE_KEY);
  return arr;
}
function toWrapped(target, item) {
  if (isReadonly(target)) {
    return isReactive(target) ? toReadonly(toReactive(item)) : toReadonly(item);
  }
  return toReactive(item);
}
const arrayInstrumentations = {
  __proto__: null,
  [Symbol.iterator]() {
    return iterator(this, Symbol.iterator, item => toWrapped(this, item));
  },
  concat() {
    for (var _len2 = arguments.length, args = new Array(_len2), _key3 = 0; _key3 < _len2; _key3++) {
      args[_key3] = arguments[_key3];
    }
    return reactiveReadArray(this).concat(...args.map(x => isArray(x) ? reactiveReadArray(x) : x));
  },
  entries() {
    return iterator(this, "entries", value => {
      value[1] = toWrapped(this, value[1]);
      return value;
    });
  },
  every(fn, thisArg) {
    return apply(this, "every", fn, thisArg, void 0, arguments);
  },
  filter(fn, thisArg) {
    return apply(this, "filter", fn, thisArg, v => v.map(item => toWrapped(this, item)), arguments);
  },
  find(fn, thisArg) {
    return apply(this, "find", fn, thisArg, item => toWrapped(this, item), arguments);
  },
  findIndex(fn, thisArg) {
    return apply(this, "findIndex", fn, thisArg, void 0, arguments);
  },
  findLast(fn, thisArg) {
    return apply(this, "findLast", fn, thisArg, item => toWrapped(this, item), arguments);
  },
  findLastIndex(fn, thisArg) {
    return apply(this, "findLastIndex", fn, thisArg, void 0, arguments);
  },
  // flat, flatMap could benefit from ARRAY_ITERATE but are not straight-forward to implement
  forEach(fn, thisArg) {
    return apply(this, "forEach", fn, thisArg, void 0, arguments);
  },
  includes() {
    for (var _len3 = arguments.length, args = new Array(_len3), _key4 = 0; _key4 < _len3; _key4++) {
      args[_key4] = arguments[_key4];
    }
    return searchProxy(this, "includes", args);
  },
  indexOf() {
    for (var _len4 = arguments.length, args = new Array(_len4), _key5 = 0; _key5 < _len4; _key5++) {
      args[_key5] = arguments[_key5];
    }
    return searchProxy(this, "indexOf", args);
  },
  join(separator) {
    return reactiveReadArray(this).join(separator);
  },
  // keys() iterator only reads `length`, no optimization required
  lastIndexOf() {
    for (var _len5 = arguments.length, args = new Array(_len5), _key6 = 0; _key6 < _len5; _key6++) {
      args[_key6] = arguments[_key6];
    }
    return searchProxy(this, "lastIndexOf", args);
  },
  map(fn, thisArg) {
    return apply(this, "map", fn, thisArg, void 0, arguments);
  },
  pop() {
    return noTracking(this, "pop");
  },
  push() {
    for (var _len6 = arguments.length, args = new Array(_len6), _key7 = 0; _key7 < _len6; _key7++) {
      args[_key7] = arguments[_key7];
    }
    return noTracking(this, "push", args);
  },
  reduce(fn) {
    for (var _len7 = arguments.length, args = new Array(_len7 > 1 ? _len7 - 1 : 0), _key8 = 1; _key8 < _len7; _key8++) {
      args[_key8 - 1] = arguments[_key8];
    }
    return reduce(this, "reduce", fn, args);
  },
  reduceRight(fn) {
    for (var _len8 = arguments.length, args = new Array(_len8 > 1 ? _len8 - 1 : 0), _key9 = 1; _key9 < _len8; _key9++) {
      args[_key9 - 1] = arguments[_key9];
    }
    return reduce(this, "reduceRight", fn, args);
  },
  shift() {
    return noTracking(this, "shift");
  },
  // slice could use ARRAY_ITERATE but also seems to beg for range tracking
  some(fn, thisArg) {
    return apply(this, "some", fn, thisArg, void 0, arguments);
  },
  splice() {
    for (var _len9 = arguments.length, args = new Array(_len9), _key0 = 0; _key0 < _len9; _key0++) {
      args[_key0] = arguments[_key0];
    }
    return noTracking(this, "splice", args);
  },
  toReversed() {
    return reactiveReadArray(this).toReversed();
  },
  toSorted(comparer) {
    return reactiveReadArray(this).toSorted(comparer);
  },
  toSpliced() {
    return reactiveReadArray(this).toSpliced(...arguments);
  },
  unshift() {
    for (var _len0 = arguments.length, args = new Array(_len0), _key1 = 0; _key1 < _len0; _key1++) {
      args[_key1] = arguments[_key1];
    }
    return noTracking(this, "unshift", args);
  },
  values() {
    return iterator(this, "values", item => toWrapped(this, item));
  }
};
function iterator(self, method, wrapValue) {
  const arr = shallowReadArray(self);
  const iter = arr[method]();
  if (arr !== self && !isShallow(self)) {
    iter._next = iter.next;
    iter.next = () => {
      const result = iter._next();
      if (!result.done) {
        result.value = wrapValue(result.value);
      }
      return result;
    };
  }
  return iter;
}
const arrayProto = Array.prototype;
function apply(self, method, fn, thisArg, wrappedRetFn, args) {
  const arr = shallowReadArray(self);
  const needsWrap = arr !== self && !isShallow(self);
  const methodFn = arr[method];
  if (methodFn !== arrayProto[method]) {
    const result2 = methodFn.apply(self, args);
    return needsWrap ? toReactive(result2) : result2;
  }
  let wrappedFn = fn;
  if (arr !== self) {
    if (needsWrap) {
      wrappedFn = function wrappedFn(item, index) {
        return fn.call(this, toWrapped(self, item), index, self);
      };
    } else if (fn.length > 2) {
      wrappedFn = function wrappedFn(item, index) {
        return fn.call(this, item, index, self);
      };
    }
  }
  const result = methodFn.call(arr, wrappedFn, thisArg);
  return needsWrap && wrappedRetFn ? wrappedRetFn(result) : result;
}
function reduce(self, method, fn, args) {
  const arr = shallowReadArray(self);
  const needsWrap = arr !== self && !isShallow(self);
  let wrappedFn = fn;
  let wrapInitialAccumulator = false;
  if (arr !== self) {
    if (needsWrap) {
      wrapInitialAccumulator = args.length === 0;
      wrappedFn = function wrappedFn(acc, item, index) {
        if (wrapInitialAccumulator) {
          wrapInitialAccumulator = false;
          acc = toWrapped(self, acc);
        }
        return fn.call(this, acc, toWrapped(self, item), index, self);
      };
    } else if (fn.length > 3) {
      wrappedFn = function wrappedFn(acc, item, index) {
        return fn.call(this, acc, item, index, self);
      };
    }
  }
  const result = arr[method](wrappedFn, ...args);
  return wrapInitialAccumulator ? toWrapped(self, result) : result;
}
function searchProxy(self, method, args) {
  const arr = toRaw(self);
  track(arr, "iterate", ARRAY_ITERATE_KEY);
  const res = arr[method](...args);
  if ((res === -1 || res === false) && isProxy(args[0])) {
    args[0] = toRaw(args[0]);
    return arr[method](...args);
  }
  return res;
}
function noTracking(self, method, args) {
  if (args === void 0) {
    args = [];
  }
  pauseTracking();
  startBatch();
  const res = toRaw(self)[method].apply(self, args);
  endBatch();
  resetTracking();
  return res;
}
const isNonTrackableKeys = /* @__PURE__ */makeMap("__proto__,__v_isRef,__isVue");
const builtInSymbols = new Set(/* @__PURE__ */Object.getOwnPropertyNames(Symbol).filter(key => key !== "arguments" && key !== "caller").map(key => Symbol[key]).filter(isSymbol));
function hasOwnProperty(key) {
  if (!isSymbol(key)) key = String(key);
  const obj = toRaw(this);
  track(obj, "has", key);
  return obj.hasOwnProperty(key);
}
class BaseReactiveHandler {
  constructor(_isReadonly, _isShallow) {
    if (_isReadonly === void 0) {
      _isReadonly = false;
    }
    if (_isShallow === void 0) {
      _isShallow = false;
    }
    this._isReadonly = _isReadonly;
    this._isShallow = _isShallow;
  }
  get(target, key, receiver) {
    if (key === "__v_skip") return target["__v_skip"];
    const isReadonly2 = this._isReadonly,
      isShallow2 = this._isShallow;
    if (key === "__v_isReactive") {
      return !isReadonly2;
    } else if (key === "__v_isReadonly") {
      return isReadonly2;
    } else if (key === "__v_isShallow") {
      return isShallow2;
    } else if (key === "__v_raw") {
      if (receiver === (isReadonly2 ? isShallow2 ? shallowReadonlyMap : readonlyMap : isShallow2 ? shallowReactiveMap : reactiveMap).get(target) ||
      // receiver is not the reactive proxy, but has the same prototype
      // this means the receiver is a user proxy of the reactive proxy
      Object.getPrototypeOf(target) === Object.getPrototypeOf(receiver)) {
        return target;
      }
      return;
    }
    const targetIsArray = isArray(target);
    if (!isReadonly2) {
      let fn;
      if (targetIsArray && (fn = arrayInstrumentations[key])) {
        return fn;
      }
      if (key === "hasOwnProperty") {
        return hasOwnProperty;
      }
    }
    const res = Reflect.get(target, key,
    // if this is a proxy wrapping a ref, return methods using the raw ref
    // as receiver so that we don't have to call `toRaw` on the ref in all
    // its class methods
    isRef(target) ? target : receiver);
    if (isSymbol(key) ? builtInSymbols.has(key) : isNonTrackableKeys(key)) {
      return res;
    }
    if (!isReadonly2) {
      track(target, "get", key);
    }
    if (isShallow2) {
      return res;
    }
    if (isRef(res)) {
      const value = targetIsArray && isIntegerKey(key) ? res : res.value;
      return isReadonly2 && isObject$2(value) ? readonly(value) : value;
    }
    if (isObject$2(res)) {
      return isReadonly2 ? readonly(res) : reactive(res);
    }
    return res;
  }
}
class MutableReactiveHandler extends BaseReactiveHandler {
  constructor(isShallow2) {
    if (isShallow2 === void 0) {
      isShallow2 = false;
    }
    super(false, isShallow2);
  }
  set(target, key, value, receiver) {
    let oldValue = target[key];
    const isArrayWithIntegerKey = isArray(target) && isIntegerKey(key);
    if (!this._isShallow) {
      const isOldValueReadonly = isReadonly(oldValue);
      if (!isShallow(value) && !isReadonly(value)) {
        oldValue = toRaw(oldValue);
        value = toRaw(value);
      }
      if (!isArrayWithIntegerKey && isRef(oldValue) && !isRef(value)) {
        if (isOldValueReadonly) {
          return true;
        } else {
          oldValue.value = value;
          return true;
        }
      }
    }
    const hadKey = isArrayWithIntegerKey ? Number(key) < target.length : hasOwn(target, key);
    const result = Reflect.set(target, key, value, isRef(target) ? target : receiver);
    if (target === toRaw(receiver)) {
      if (!hadKey) {
        trigger(target, "add", key, value);
      } else if (hasChanged(value, oldValue)) {
        trigger(target, "set", key, value);
      }
    }
    return result;
  }
  deleteProperty(target, key) {
    const hadKey = hasOwn(target, key);
    target[key];
    const result = Reflect.deleteProperty(target, key);
    if (result && hadKey) {
      trigger(target, "delete", key, void 0);
    }
    return result;
  }
  has(target, key) {
    const result = Reflect.has(target, key);
    if (!isSymbol(key) || !builtInSymbols.has(key)) {
      track(target, "has", key);
    }
    return result;
  }
  ownKeys(target) {
    track(target, "iterate", isArray(target) ? "length" : ITERATE_KEY);
    return Reflect.ownKeys(target);
  }
}
class ReadonlyReactiveHandler extends BaseReactiveHandler {
  constructor(isShallow2) {
    if (isShallow2 === void 0) {
      isShallow2 = false;
    }
    super(true, isShallow2);
  }
  set(target, key) {
    return true;
  }
  deleteProperty(target, key) {
    return true;
  }
}
const mutableHandlers = /* @__PURE__ */new MutableReactiveHandler();
const readonlyHandlers = /* @__PURE__ */new ReadonlyReactiveHandler();
const shallowReactiveHandlers = /* @__PURE__ */new MutableReactiveHandler(true);
const shallowReadonlyHandlers = /* @__PURE__ */new ReadonlyReactiveHandler(true);
const toShallow = value => value;
const getProto = v => Reflect.getPrototypeOf(v);
function createIterableMethod(method, isReadonly2, isShallow2) {
  return function () {
    const target = this["__v_raw"];
    const rawTarget = toRaw(target);
    const targetIsMap = isMap(rawTarget);
    const isPair = method === "entries" || method === Symbol.iterator && targetIsMap;
    const isKeyOnly = method === "keys" && targetIsMap;
    const innerIterator = target[method](...arguments);
    const wrap = isShallow2 ? toShallow : isReadonly2 ? toReadonly : toReactive;
    !isReadonly2 && track(rawTarget, "iterate", isKeyOnly ? MAP_KEY_ITERATE_KEY : ITERATE_KEY);
    return extend$2(
    // inheriting all iterator properties
    Object.create(innerIterator), {
      // iterator protocol
      next() {
        const _innerIterator$next = innerIterator.next(),
          value = _innerIterator$next.value,
          done = _innerIterator$next.done;
        return done ? {
          value,
          done
        } : {
          value: isPair ? [wrap(value[0]), wrap(value[1])] : wrap(value),
          done
        };
      }
    });
  };
}
function createReadonlyMethod(type) {
  return function () {
    return type === "delete" ? false : type === "clear" ? void 0 : this;
  };
}
function createInstrumentations(readonly, shallow) {
  const instrumentations = {
    get(key) {
      const target = this["__v_raw"];
      const rawTarget = toRaw(target);
      const rawKey = toRaw(key);
      if (!readonly) {
        if (hasChanged(key, rawKey)) {
          track(rawTarget, "get", key);
        }
        track(rawTarget, "get", rawKey);
      }
      const _getProto = getProto(rawTarget),
        has = _getProto.has;
      const wrap = shallow ? toShallow : readonly ? toReadonly : toReactive;
      if (has.call(rawTarget, key)) {
        return wrap(target.get(key));
      } else if (has.call(rawTarget, rawKey)) {
        return wrap(target.get(rawKey));
      } else if (target !== rawTarget) {
        target.get(key);
      }
    },
    get size() {
      const target = this["__v_raw"];
      !readonly && track(toRaw(target), "iterate", ITERATE_KEY);
      return target.size;
    },
    has(key) {
      const target = this["__v_raw"];
      const rawTarget = toRaw(target);
      const rawKey = toRaw(key);
      if (!readonly) {
        if (hasChanged(key, rawKey)) {
          track(rawTarget, "has", key);
        }
        track(rawTarget, "has", rawKey);
      }
      return key === rawKey ? target.has(key) : target.has(key) || target.has(rawKey);
    },
    forEach(callback, thisArg) {
      const observed = this;
      const target = observed["__v_raw"];
      const rawTarget = toRaw(target);
      const wrap = shallow ? toShallow : readonly ? toReadonly : toReactive;
      !readonly && track(rawTarget, "iterate", ITERATE_KEY);
      return target.forEach((value, key) => {
        return callback.call(thisArg, wrap(value), wrap(key), observed);
      });
    }
  };
  extend$2(instrumentations, readonly ? {
    add: createReadonlyMethod("add"),
    set: createReadonlyMethod("set"),
    delete: createReadonlyMethod("delete"),
    clear: createReadonlyMethod("clear")
  } : {
    add(value) {
      const target = toRaw(this);
      const proto = getProto(target);
      const rawValue = toRaw(value);
      const valueToAdd = !shallow && !isShallow(value) && !isReadonly(value) ? rawValue : value;
      const hadKey = proto.has.call(target, valueToAdd) || hasChanged(value, valueToAdd) && proto.has.call(target, value) || hasChanged(rawValue, valueToAdd) && proto.has.call(target, rawValue);
      if (!hadKey) {
        target.add(valueToAdd);
        trigger(target, "add", valueToAdd, valueToAdd);
      }
      return this;
    },
    set(key, value) {
      if (!shallow && !isShallow(value) && !isReadonly(value)) {
        value = toRaw(value);
      }
      const target = toRaw(this);
      const _getProto2 = getProto(target),
        has = _getProto2.has,
        get = _getProto2.get;
      let hadKey = has.call(target, key);
      if (!hadKey) {
        key = toRaw(key);
        hadKey = has.call(target, key);
      }
      const oldValue = get.call(target, key);
      target.set(key, value);
      if (!hadKey) {
        trigger(target, "add", key, value);
      } else if (hasChanged(value, oldValue)) {
        trigger(target, "set", key, value);
      }
      return this;
    },
    delete(key) {
      const target = toRaw(this);
      const _getProto3 = getProto(target),
        has = _getProto3.has,
        get = _getProto3.get;
      let hadKey = has.call(target, key);
      if (!hadKey) {
        key = toRaw(key);
        hadKey = has.call(target, key);
      }
      get ? get.call(target, key) : void 0;
      const result = target.delete(key);
      if (hadKey) {
        trigger(target, "delete", key, void 0);
      }
      return result;
    },
    clear() {
      const target = toRaw(this);
      const hadItems = target.size !== 0;
      const result = target.clear();
      if (hadItems) {
        trigger(target, "clear", void 0, void 0);
      }
      return result;
    }
  });
  const iteratorMethods = ["keys", "values", "entries", Symbol.iterator];
  iteratorMethods.forEach(method => {
    instrumentations[method] = createIterableMethod(method, readonly, shallow);
  });
  return instrumentations;
}
function createInstrumentationGetter(isReadonly2, shallow) {
  const instrumentations = createInstrumentations(isReadonly2, shallow);
  return (target, key, receiver) => {
    if (key === "__v_isReactive") {
      return !isReadonly2;
    } else if (key === "__v_isReadonly") {
      return isReadonly2;
    } else if (key === "__v_raw") {
      return target;
    }
    return Reflect.get(hasOwn(instrumentations, key) && key in target ? instrumentations : target, key, receiver);
  };
}
const mutableCollectionHandlers = {
  get: /* @__PURE__ */createInstrumentationGetter(false, false)
};
const shallowCollectionHandlers = {
  get: /* @__PURE__ */createInstrumentationGetter(false, true)
};
const readonlyCollectionHandlers = {
  get: /* @__PURE__ */createInstrumentationGetter(true, false)
};
const shallowReadonlyCollectionHandlers = {
  get: /* @__PURE__ */createInstrumentationGetter(true, true)
};
const reactiveMap = /* @__PURE__ */new WeakMap();
const shallowReactiveMap = /* @__PURE__ */new WeakMap();
const readonlyMap = /* @__PURE__ */new WeakMap();
const shallowReadonlyMap = /* @__PURE__ */new WeakMap();
function targetTypeMap(rawType) {
  switch (rawType) {
    case "Object":
    case "Array":
      return 1 /* COMMON */;
    case "Map":
    case "Set":
    case "WeakMap":
    case "WeakSet":
      return 2 /* COLLECTION */;
    default:
      return 0 /* INVALID */;
  }
}
function getTargetType(value) {
  return value["__v_skip"] || !Object.isExtensible(value) ? 0 /* INVALID */ : targetTypeMap(toRawType(value));
}
// @__NO_SIDE_EFFECTS__
function reactive(target) {
  if (/* @__PURE__ */isReadonly(target)) {
    return target;
  }
  return createReactiveObject(target, false, mutableHandlers, mutableCollectionHandlers, reactiveMap);
}
// @__NO_SIDE_EFFECTS__
function shallowReactive(target) {
  return createReactiveObject(target, false, shallowReactiveHandlers, shallowCollectionHandlers, shallowReactiveMap);
}
// @__NO_SIDE_EFFECTS__
function readonly(target) {
  return createReactiveObject(target, true, readonlyHandlers, readonlyCollectionHandlers, readonlyMap);
}
// @__NO_SIDE_EFFECTS__
function shallowReadonly(target) {
  return createReactiveObject(target, true, shallowReadonlyHandlers, shallowReadonlyCollectionHandlers, shallowReadonlyMap);
}
function createReactiveObject(target, isReadonly2, baseHandlers, collectionHandlers, proxyMap) {
  if (!isObject$2(target)) {
    return target;
  }
  if (target["__v_raw"] && !(isReadonly2 && target["__v_isReactive"])) {
    return target;
  }
  const targetType = getTargetType(target);
  if (targetType === 0 /* INVALID */) {
    return target;
  }
  const existingProxy = proxyMap.get(target);
  if (existingProxy) {
    return existingProxy;
  }
  const proxy = new Proxy(target, targetType === 2 /* COLLECTION */ ? collectionHandlers : baseHandlers);
  proxyMap.set(target, proxy);
  return proxy;
}
// @__NO_SIDE_EFFECTS__
function isReactive(value) {
  if (/* @__PURE__ */isReadonly(value)) {
    return /* @__PURE__ */isReactive(value["__v_raw"]);
  }
  return !!(value && value["__v_isReactive"]);
}
// @__NO_SIDE_EFFECTS__
function isReadonly(value) {
  return !!(value && value["__v_isReadonly"]);
}
// @__NO_SIDE_EFFECTS__
function isShallow(value) {
  return !!(value && value["__v_isShallow"]);
}
// @__NO_SIDE_EFFECTS__
function isProxy(value) {
  return value ? !!value["__v_raw"] : false;
}
// @__NO_SIDE_EFFECTS__
function toRaw(observed) {
  const raw = observed && observed["__v_raw"];
  return raw ? /* @__PURE__ */toRaw(raw) : observed;
}
function markRaw(value) {
  if (!hasOwn(value, "__v_skip") && Object.isExtensible(value)) {
    def(value, "__v_skip", true);
  }
  return value;
}
const toReactive = value => isObject$2(value) ? /* @__PURE__ */reactive(value) : value;
const toReadonly = value => isObject$2(value) ? /* @__PURE__ */readonly(value) : value;

// @__NO_SIDE_EFFECTS__
function isRef(r) {
  return r ? r["__v_isRef"] === true : false;
}
// @__NO_SIDE_EFFECTS__
function ref(value) {
  return createRef(value, false);
}
// @__NO_SIDE_EFFECTS__
function shallowRef(value) {
  return createRef(value, true);
}
function createRef(rawValue, shallow) {
  if (/* @__PURE__ */isRef(rawValue)) {
    return rawValue;
  }
  return new RefImpl(rawValue, shallow);
}
class RefImpl {
  constructor(value, isShallow2) {
    this.dep = new Dep();
    this["__v_isRef"] = true;
    this["__v_isShallow"] = false;
    this._rawValue = isShallow2 ? value : toRaw(value);
    this._value = isShallow2 ? value : toReactive(value);
    this["__v_isShallow"] = isShallow2;
  }
  get value() {
    {
      this.dep.track();
    }
    return this._value;
  }
  set value(newValue) {
    const oldValue = this._rawValue;
    const useDirectValue = this["__v_isShallow"] || isShallow(newValue) || isReadonly(newValue);
    newValue = useDirectValue ? newValue : toRaw(newValue);
    if (hasChanged(newValue, oldValue)) {
      this._rawValue = newValue;
      this._value = useDirectValue ? newValue : toReactive(newValue);
      {
        this.dep.trigger();
      }
    }
  }
}
function unref(ref2) {
  return /* @__PURE__ */isRef(ref2) ? ref2.value : ref2;
}
function toValue$1(source) {
  return isFunction(source) ? source() : unref(source);
}
const shallowUnwrapHandlers = {
  get: (target, key, receiver) => key === "__v_raw" ? target : unref(Reflect.get(target, key, receiver)),
  set: (target, key, value, receiver) => {
    const oldValue = target[key];
    if (/* @__PURE__ */isRef(oldValue) && ! /* @__PURE__ */isRef(value)) {
      oldValue.value = value;
      return true;
    } else {
      return Reflect.set(target, key, value, receiver);
    }
  }
};
function proxyRefs(objectWithRefs) {
  return isReactive(objectWithRefs) ? objectWithRefs : new Proxy(objectWithRefs, shallowUnwrapHandlers);
}
class CustomRefImpl {
  constructor(factory) {
    this["__v_isRef"] = true;
    this._value = void 0;
    const dep = this.dep = new Dep();
    const _factory = factory(dep.track.bind(dep), dep.trigger.bind(dep)),
      get = _factory.get,
      set = _factory.set;
    this._get = get;
    this._set = set;
  }
  get value() {
    return this._value = this._get();
  }
  set value(newVal) {
    this._set(newVal);
  }
}
function customRef(factory) {
  return new CustomRefImpl(factory);
}
// @__NO_SIDE_EFFECTS__
function toRefs$1(object) {
  const ret = isArray(object) ? new Array(object.length) : {};
  for (const key in object) {
    ret[key] = propertyToRef(object, key);
  }
  return ret;
}
class ObjectRefImpl {
  constructor(_object, _key, _defaultValue) {
    this._object = _object;
    this._key = _key;
    this._defaultValue = _defaultValue;
    this["__v_isRef"] = true;
    this._value = void 0;
    this._raw = toRaw(_object);
    let shallow = true;
    let obj = _object;
    if (!isArray(_object) || !isIntegerKey(String(_key))) {
      do {
        shallow = !isProxy(obj) || isShallow(obj);
      } while (shallow && (obj = obj["__v_raw"]));
    }
    this._shallow = shallow;
  }
  get value() {
    let val = this._object[this._key];
    if (this._shallow) {
      val = unref(val);
    }
    return this._value = val === void 0 ? this._defaultValue : val;
  }
  set value(newVal) {
    if (this._shallow && /* @__PURE__ */isRef(this._raw[this._key])) {
      const nestedRef = this._object[this._key];
      if (/* @__PURE__ */isRef(nestedRef)) {
        nestedRef.value = newVal;
        return;
      }
    }
    this._object[this._key] = newVal;
  }
  get dep() {
    return getDepFromReactive(this._raw, this._key);
  }
}
class GetterRefImpl {
  constructor(_getter) {
    this._getter = _getter;
    this["__v_isRef"] = true;
    this["__v_isReadonly"] = true;
    this._value = void 0;
  }
  get value() {
    return this._value = this._getter();
  }
}
// @__NO_SIDE_EFFECTS__
function toRef(source, key, defaultValue) {
  if (/* @__PURE__ */isRef(source)) {
    return source;
  } else if (isFunction(source)) {
    return new GetterRefImpl(source);
  } else if (isObject$2(source) && arguments.length > 1) {
    return propertyToRef(source, key, defaultValue);
  } else {
    return /* @__PURE__ */ref(source);
  }
}
function propertyToRef(source, key, defaultValue) {
  return new ObjectRefImpl(source, key, defaultValue);
}
class ComputedRefImpl {
  constructor(fn, setter, isSSR) {
    this.fn = fn;
    this.setter = setter;
    /**
     * @internal
     */
    this._value = void 0;
    /**
     * @internal
     */
    this.dep = new Dep(this);
    /**
     * @internal
     */
    this.__v_isRef = true;
    // TODO isolatedDeclarations "__v_isReadonly"
    // A computed is also a subscriber that tracks other deps
    /**
     * @internal
     */
    this.deps = void 0;
    /**
     * @internal
     */
    this.depsTail = void 0;
    /**
     * @internal
     */
    this.flags = 16;
    /**
     * @internal
     */
    this.globalVersion = globalVersion - 1;
    /**
     * @internal
     */
    this.next = void 0;
    // for backwards compat
    this.effect = this;
    this["__v_isReadonly"] = !setter;
    this.isSSR = isSSR;
  }
  /**
   * @internal
   */
  notify() {
    this.flags |= 16;
    if (!(this.flags & 8) &&
    // avoid infinite self recursion
    activeSub !== this) {
      batch(this, true);
      return true;
    }
  }
  get value() {
    const link = this.dep.track();
    refreshComputed(this);
    if (link) {
      link.version = this.dep.version;
    }
    return this._value;
  }
  set value(newValue) {
    if (this.setter) {
      this.setter(newValue);
    }
  }
}
// @__NO_SIDE_EFFECTS__
function computed$1(getterOrOptions, debugOptions, isSSR) {
  if (isSSR === void 0) {
    isSSR = false;
  }
  let getter;
  let setter;
  if (isFunction(getterOrOptions)) {
    getter = getterOrOptions;
  } else {
    getter = getterOrOptions.get;
    setter = getterOrOptions.set;
  }
  const cRef = new ComputedRefImpl(getter, setter, isSSR);
  return cRef;
}
const INITIAL_WATCHER_VALUE = {};
const cleanupMap = /* @__PURE__ */new WeakMap();
let activeWatcher = void 0;
function onWatcherCleanup(cleanupFn, failSilently, owner) {
  if (owner === void 0) {
    owner = activeWatcher;
  }
  if (owner) {
    let cleanups = cleanupMap.get(owner);
    if (!cleanups) cleanupMap.set(owner, cleanups = []);
    cleanups.push(cleanupFn);
  }
}
function watch$1(source, cb, options) {
  if (options === void 0) {
    options = EMPTY_OBJ;
  }
  const _options = options,
    immediate = _options.immediate,
    deep = _options.deep,
    once = _options.once,
    scheduler = _options.scheduler,
    augmentJob = _options.augmentJob,
    call = _options.call;
  const reactiveGetter = source2 => {
    if (deep) return source2;
    if (isShallow(source2) || deep === false || deep === 0) return traverse(source2, 1);
    return traverse(source2);
  };
  let effect;
  let getter;
  let cleanup;
  let boundCleanup;
  let forceTrigger = false;
  let isMultiSource = false;
  if (isRef(source)) {
    getter = () => source.value;
    forceTrigger = isShallow(source);
  } else if (isReactive(source)) {
    getter = () => reactiveGetter(source);
    forceTrigger = true;
  } else if (isArray(source)) {
    isMultiSource = true;
    forceTrigger = source.some(s => isReactive(s) || isShallow(s));
    getter = () => source.map(s => {
      if (isRef(s)) {
        return s.value;
      } else if (isReactive(s)) {
        return reactiveGetter(s);
      } else if (isFunction(s)) {
        return call ? call(s, 2) : s();
      } else ;
    });
  } else if (isFunction(source)) {
    if (cb) {
      getter = call ? () => call(source, 2) : source;
    } else {
      getter = () => {
        if (cleanup) {
          pauseTracking();
          try {
            cleanup();
          } finally {
            resetTracking();
          }
        }
        const currentEffect = activeWatcher;
        activeWatcher = effect;
        try {
          return call ? call(source, 3, [boundCleanup]) : source(boundCleanup);
        } finally {
          activeWatcher = currentEffect;
        }
      };
    }
  } else {
    getter = NOOP;
  }
  if (cb && deep) {
    const baseGetter = getter;
    const depth = deep === true ? Infinity : deep;
    getter = () => traverse(baseGetter(), depth);
  }
  const scope = getCurrentScope();
  const watchHandle = () => {
    effect.stop();
    if (scope && scope.active) {
      remove$2(scope.effects, effect);
    }
  };
  if (once && cb) {
    const _cb = cb;
    cb = function cb() {
      _cb(...arguments);
      watchHandle();
    };
  }
  let oldValue = isMultiSource ? new Array(source.length).fill(INITIAL_WATCHER_VALUE) : INITIAL_WATCHER_VALUE;
  const job = immediateFirstRun => {
    if (!(effect.flags & 1) || !effect.dirty && !immediateFirstRun) {
      return;
    }
    if (cb) {
      const newValue = effect.run();
      if (deep || forceTrigger || (isMultiSource ? newValue.some((v, i) => hasChanged(v, oldValue[i])) : hasChanged(newValue, oldValue))) {
        if (cleanup) {
          cleanup();
        }
        const currentWatcher = activeWatcher;
        activeWatcher = effect;
        try {
          const args = [newValue,
          // pass undefined as the old value when it's changed for the first time
          oldValue === INITIAL_WATCHER_VALUE ? void 0 : isMultiSource && oldValue[0] === INITIAL_WATCHER_VALUE ? [] : oldValue, boundCleanup];
          oldValue = newValue;
          call ? call(cb, 3, args) :
          // @ts-expect-error
          cb(...args);
        } finally {
          activeWatcher = currentWatcher;
        }
      }
    } else {
      effect.run();
    }
  };
  if (augmentJob) {
    augmentJob(job);
  }
  effect = new ReactiveEffect(getter);
  effect.scheduler = scheduler ? () => scheduler(job, false) : job;
  boundCleanup = fn => onWatcherCleanup(fn, false, effect);
  cleanup = effect.onStop = () => {
    const cleanups = cleanupMap.get(effect);
    if (cleanups) {
      if (call) {
        call(cleanups, 4);
      } else {
        for (const cleanup2 of cleanups) cleanup2();
      }
      cleanupMap.delete(effect);
    }
  };
  if (cb) {
    if (immediate) {
      job(true);
    } else {
      oldValue = effect.run();
    }
  } else if (scheduler) {
    scheduler(job.bind(null, true), true);
  } else {
    effect.run();
  }
  watchHandle.pause = effect.pause.bind(effect);
  watchHandle.resume = effect.resume.bind(effect);
  watchHandle.stop = watchHandle;
  return watchHandle;
}
function traverse(value, depth, seen) {
  if (depth === void 0) {
    depth = Infinity;
  }
  if (depth <= 0 || !isObject$2(value) || value["__v_skip"]) {
    return value;
  }
  seen = seen || /* @__PURE__ */new Map();
  if ((seen.get(value) || 0) >= depth) {
    return value;
  }
  seen.set(value, depth);
  depth--;
  if (isRef(value)) {
    traverse(value.value, depth, seen);
  } else if (isArray(value)) {
    for (let i = 0; i < value.length; i++) {
      traverse(value[i], depth, seen);
    }
  } else if (isSet(value) || isMap(value)) {
    value.forEach(v => {
      traverse(v, depth, seen);
    });
  } else if (isPlainObject(value)) {
    for (const key in value) {
      traverse(value[key], depth, seen);
    }
    for (const key of Object.getOwnPropertySymbols(value)) {
      if (Object.prototype.propertyIsEnumerable.call(value, key)) {
        traverse(value[key], depth, seen);
      }
    }
  }
  return value;
}

/**
* @vue/runtime-core v3.5.30
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
const stack = [];
let isWarning = false;
function warn$1(msg) {
  if (isWarning) return;
  isWarning = true;
  pauseTracking();
  const instance = stack.length ? stack[stack.length - 1].component : null;
  const appWarnHandler = instance && instance.appContext.config.warnHandler;
  const trace = getComponentTrace();
  for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
    args[_key - 1] = arguments[_key];
  }
  if (appWarnHandler) {
    callWithErrorHandling(appWarnHandler, instance, 11, [
    // eslint-disable-next-line no-restricted-syntax
    msg + args.map(a => {
      var _a, _b;
      return (_b = (_a = a.toString) == null ? void 0 : _a.call(a)) != null ? _b : JSON.stringify(a);
    }).join(""), instance && instance.proxy, trace.map(_ref => {
      let vnode = _ref.vnode;
      return "at <" + formatComponentName(instance, vnode.type) + ">";
    }).join("\n"), trace]);
  } else {
    const warnArgs = ["[Vue warn]: " + msg, ...args];
    if (trace.length &&
    // avoid spamming console during tests
    true) {
      warnArgs.push("\n", ...formatTrace(trace));
    }
    console.warn(...warnArgs);
  }
  resetTracking();
  isWarning = false;
}
function getComponentTrace() {
  let currentVNode = stack[stack.length - 1];
  if (!currentVNode) {
    return [];
  }
  const normalizedStack = [];
  while (currentVNode) {
    const last = normalizedStack[0];
    if (last && last.vnode === currentVNode) {
      last.recurseCount++;
    } else {
      normalizedStack.push({
        vnode: currentVNode,
        recurseCount: 0
      });
    }
    const parentInstance = currentVNode.component && currentVNode.component.parent;
    currentVNode = parentInstance && parentInstance.vnode;
  }
  return normalizedStack;
}
function formatTrace(trace) {
  const logs = [];
  trace.forEach((entry, i) => {
    logs.push(...(i === 0 ? [] : ["\n"]), ...formatTraceEntry(entry));
  });
  return logs;
}
function formatTraceEntry(_ref2) {
  let vnode = _ref2.vnode,
    recurseCount = _ref2.recurseCount;
  const postfix = recurseCount > 0 ? "... (" + recurseCount + " recursive calls)" : "";
  const isRoot = vnode.component ? vnode.component.parent == null : false;
  const open = " at <" + formatComponentName(vnode.component, vnode.type, isRoot);
  const close = ">" + postfix;
  return vnode.props ? [open, ...formatProps(vnode.props), close] : [open + close];
}
function formatProps(props) {
  const res = [];
  const keys = Object.keys(props);
  keys.slice(0, 3).forEach(key => {
    res.push(...formatProp(key, props[key]));
  });
  if (keys.length > 3) {
    res.push(" ...");
  }
  return res;
}
function formatProp(key, value, raw) {
  if (isString(value)) {
    value = JSON.stringify(value);
    return raw ? value : [key + "=" + value];
  } else if (typeof value === "number" || typeof value === "boolean" || value == null) {
    return raw ? value : [key + "=" + value];
  } else if (isRef(value)) {
    value = formatProp(key, toRaw(value.value), true);
    return raw ? value : [key + "=Ref<", value, ">"];
  } else if (isFunction(value)) {
    return [key + "=fn" + (value.name ? "<" + value.name + ">" : "")];
  } else {
    value = toRaw(value);
    return raw ? value : [key + "=", value];
  }
}
function callWithErrorHandling(fn, instance, type, args) {
  try {
    return args ? fn(...args) : fn();
  } catch (err) {
    handleError(err, instance, type);
  }
}
function callWithAsyncErrorHandling(fn, instance, type, args) {
  if (isFunction(fn)) {
    const res = callWithErrorHandling(fn, instance, type, args);
    if (res && isPromise$1(res)) {
      res.catch(err => {
        handleError(err, instance, type);
      });
    }
    return res;
  }
  if (isArray(fn)) {
    const values = [];
    for (let i = 0; i < fn.length; i++) {
      values.push(callWithAsyncErrorHandling(fn[i], instance, type, args));
    }
    return values;
  }
}
function handleError(err, instance, type, throwInDev) {
  if (throwInDev === void 0) {
    throwInDev = true;
  }
  const contextVNode = instance ? instance.vnode : null;
  const _ref3 = instance && instance.appContext.config || EMPTY_OBJ,
    errorHandler = _ref3.errorHandler,
    throwUnhandledErrorInProduction = _ref3.throwUnhandledErrorInProduction;
  if (instance) {
    let cur = instance.parent;
    const exposedInstance = instance.proxy;
    const errorInfo = "https://vuejs.org/error-reference/#runtime-" + type;
    while (cur) {
      const errorCapturedHooks = cur.ec;
      if (errorCapturedHooks) {
        for (let i = 0; i < errorCapturedHooks.length; i++) {
          if (errorCapturedHooks[i](err, exposedInstance, errorInfo) === false) {
            return;
          }
        }
      }
      cur = cur.parent;
    }
    if (errorHandler) {
      pauseTracking();
      callWithErrorHandling(errorHandler, null, 10, [err, exposedInstance, errorInfo]);
      resetTracking();
      return;
    }
  }
  logError(err, type, contextVNode, throwInDev, throwUnhandledErrorInProduction);
}
function logError(err, type, contextVNode, throwInDev, throwInProd) {
  if (throwInProd === void 0) {
    throwInProd = false;
  }
  if (throwInProd) {
    throw err;
  } else {
    console.error(err);
  }
}
const queue = [];
let flushIndex = -1;
const pendingPostFlushCbs = [];
let activePostFlushCbs = null;
let postFlushIndex = 0;
const resolvedPromise = /* @__PURE__ */Promise.resolve();
let currentFlushPromise = null;
function nextTick(fn) {
  const p = currentFlushPromise || resolvedPromise;
  return fn ? p.then(this ? fn.bind(this) : fn) : p;
}
function findInsertionIndex(id) {
  let start = flushIndex + 1;
  let end = queue.length;
  while (start < end) {
    const middle = start + end >>> 1;
    const middleJob = queue[middle];
    const middleJobId = getId(middleJob);
    if (middleJobId < id || middleJobId === id && middleJob.flags & 2) {
      start = middle + 1;
    } else {
      end = middle;
    }
  }
  return start;
}
function queueJob(job) {
  if (!(job.flags & 1)) {
    const jobId = getId(job);
    const lastJob = queue[queue.length - 1];
    if (!lastJob ||
    // fast path when the job id is larger than the tail
    !(job.flags & 2) && jobId >= getId(lastJob)) {
      queue.push(job);
    } else {
      queue.splice(findInsertionIndex(jobId), 0, job);
    }
    job.flags |= 1;
    queueFlush();
  }
}
function queueFlush() {
  if (!currentFlushPromise) {
    currentFlushPromise = resolvedPromise.then(flushJobs);
  }
}
function queuePostFlushCb(cb) {
  if (!isArray(cb)) {
    if (activePostFlushCbs && cb.id === -1) {
      activePostFlushCbs.splice(postFlushIndex + 1, 0, cb);
    } else if (!(cb.flags & 1)) {
      pendingPostFlushCbs.push(cb);
      cb.flags |= 1;
    }
  } else {
    pendingPostFlushCbs.push(...cb);
  }
  queueFlush();
}
function flushPreFlushCbs(instance, seen, i) {
  if (i === void 0) {
    i = flushIndex + 1;
  }
  for (; i < queue.length; i++) {
    const cb = queue[i];
    if (cb && cb.flags & 2) {
      if (instance && cb.id !== instance.uid) {
        continue;
      }
      queue.splice(i, 1);
      i--;
      if (cb.flags & 4) {
        cb.flags &= -2;
      }
      cb();
      if (!(cb.flags & 4)) {
        cb.flags &= -2;
      }
    }
  }
}
function flushPostFlushCbs(seen) {
  if (pendingPostFlushCbs.length) {
    const deduped = [...new Set(pendingPostFlushCbs)].sort((a, b) => getId(a) - getId(b));
    pendingPostFlushCbs.length = 0;
    if (activePostFlushCbs) {
      activePostFlushCbs.push(...deduped);
      return;
    }
    activePostFlushCbs = deduped;
    for (postFlushIndex = 0; postFlushIndex < activePostFlushCbs.length; postFlushIndex++) {
      const cb = activePostFlushCbs[postFlushIndex];
      if (cb.flags & 4) {
        cb.flags &= -2;
      }
      if (!(cb.flags & 8)) cb();
      cb.flags &= -2;
    }
    activePostFlushCbs = null;
    postFlushIndex = 0;
  }
}
const getId = job => job.id == null ? job.flags & 2 ? -1 : Infinity : job.id;
function flushJobs(seen) {
  const check = NOOP;
  try {
    for (flushIndex = 0; flushIndex < queue.length; flushIndex++) {
      const job = queue[flushIndex];
      if (job && !(job.flags & 8)) {
        if (!!("production" !== "production") && check(job)) ;
        if (job.flags & 4) {
          job.flags &= ~1;
        }
        callWithErrorHandling(job, job.i, job.i ? 15 : 14);
        if (!(job.flags & 4)) {
          job.flags &= ~1;
        }
      }
    }
  } finally {
    for (; flushIndex < queue.length; flushIndex++) {
      const job = queue[flushIndex];
      if (job) {
        job.flags &= -2;
      }
    }
    flushIndex = -1;
    queue.length = 0;
    flushPostFlushCbs();
    currentFlushPromise = null;
    if (queue.length || pendingPostFlushCbs.length) {
      flushJobs();
    }
  }
}
let currentRenderingInstance = null;
let currentScopeId = null;
function setCurrentRenderingInstance(instance) {
  const prev = currentRenderingInstance;
  currentRenderingInstance = instance;
  currentScopeId = instance && instance.type.__scopeId || null;
  return prev;
}
function withCtx(fn, ctx, isNonScopedSlot) {
  if (ctx === void 0) {
    ctx = currentRenderingInstance;
  }
  if (!ctx) return fn;
  if (fn._n) {
    return fn;
  }
  const _renderFnWithContext = function renderFnWithContext() {
    if (_renderFnWithContext._d) {
      setBlockTracking(-1);
    }
    const prevInstance = setCurrentRenderingInstance(ctx);
    let res;
    try {
      res = fn(...arguments);
    } finally {
      setCurrentRenderingInstance(prevInstance);
      if (_renderFnWithContext._d) {
        setBlockTracking(1);
      }
    }
    return res;
  };
  _renderFnWithContext._n = true;
  _renderFnWithContext._c = true;
  _renderFnWithContext._d = true;
  return _renderFnWithContext;
}
function invokeDirectiveHook(vnode, prevVNode, instance, name) {
  const bindings = vnode.dirs;
  const oldBindings = prevVNode && prevVNode.dirs;
  for (let i = 0; i < bindings.length; i++) {
    const binding = bindings[i];
    if (oldBindings) {
      binding.oldValue = oldBindings[i].value;
    }
    let hook = binding.dir[name];
    if (hook) {
      pauseTracking();
      callWithAsyncErrorHandling(hook, instance, 8, [vnode.el, binding, vnode, prevVNode]);
      resetTracking();
    }
  }
}
function provide(key, value) {
  if (currentInstance) {
    let provides = currentInstance.provides;
    const parentProvides = currentInstance.parent && currentInstance.parent.provides;
    if (parentProvides === provides) {
      provides = currentInstance.provides = Object.create(parentProvides);
    }
    provides[key] = value;
  }
}
function inject(key, defaultValue, treatDefaultAsFactory) {
  if (treatDefaultAsFactory === void 0) {
    treatDefaultAsFactory = false;
  }
  const instance = getCurrentInstance();
  if (instance || currentApp) {
    let provides = currentApp ? currentApp._context.provides : instance ? instance.parent == null || instance.ce ? instance.vnode.appContext && instance.vnode.appContext.provides : instance.parent.provides : void 0;
    if (provides && key in provides) {
      return provides[key];
    } else if (arguments.length > 1) {
      return treatDefaultAsFactory && isFunction(defaultValue) ? defaultValue.call(instance && instance.proxy) : defaultValue;
    } else ;
  }
}
const ssrContextKey = /* @__PURE__ */Symbol.for("v-scx");
const useSSRContext = () => {
  {
    const ctx = inject(ssrContextKey);
    return ctx;
  }
};
function watchEffect(effect, options) {
  return doWatch(effect, null, options);
}
function watch(source, cb, options) {
  return doWatch(source, cb, options);
}
function doWatch(source, cb, options) {
  if (options === void 0) {
    options = EMPTY_OBJ;
  }
  const _options = options,
    immediate = _options.immediate;
    _options.deep;
    const flush = _options.flush;
    _options.once;
  const baseWatchOptions = extend$2({}, options);
  const runsImmediately = cb && immediate || !cb && flush !== "post";
  let ssrCleanup;
  if (isInSSRComponentSetup) {
    if (flush === "sync") {
      const ctx = useSSRContext();
      ssrCleanup = ctx.__watcherHandles || (ctx.__watcherHandles = []);
    } else if (!runsImmediately) {
      const watchStopHandle = () => {};
      watchStopHandle.stop = NOOP;
      watchStopHandle.resume = NOOP;
      watchStopHandle.pause = NOOP;
      return watchStopHandle;
    }
  }
  const instance = currentInstance;
  baseWatchOptions.call = (fn, type, args) => callWithAsyncErrorHandling(fn, instance, type, args);
  let isPre = false;
  if (flush === "post") {
    baseWatchOptions.scheduler = job => {
      queuePostRenderEffect(job, instance && instance.suspense);
    };
  } else if (flush !== "sync") {
    isPre = true;
    baseWatchOptions.scheduler = (job, isFirstRun) => {
      if (isFirstRun) {
        job();
      } else {
        queueJob(job);
      }
    };
  }
  baseWatchOptions.augmentJob = job => {
    if (cb) {
      job.flags |= 4;
    }
    if (isPre) {
      job.flags |= 2;
      if (instance) {
        job.id = instance.uid;
        job.i = instance;
      }
    }
  };
  const watchHandle = watch$1(source, cb, baseWatchOptions);
  if (isInSSRComponentSetup) {
    if (ssrCleanup) {
      ssrCleanup.push(watchHandle);
    } else if (runsImmediately) {
      watchHandle();
    }
  }
  return watchHandle;
}
function instanceWatch(source, value, options) {
  const publicThis = this.proxy;
  const getter = isString(source) ? source.includes(".") ? createPathGetter(publicThis, source) : () => publicThis[source] : source.bind(publicThis, publicThis);
  let cb;
  if (isFunction(value)) {
    cb = value;
  } else {
    cb = value.handler;
    options = value;
  }
  const reset = setCurrentInstance(this);
  const res = doWatch(getter, cb.bind(publicThis), options);
  reset();
  return res;
}
function createPathGetter(ctx, path) {
  const segments = path.split(".");
  return () => {
    let cur = ctx;
    for (let i = 0; i < segments.length && cur; i++) {
      cur = cur[segments[i]];
    }
    return cur;
  };
}
const TeleportEndKey = /* @__PURE__ */Symbol("_vte");
const isTeleport = type => type.__isTeleport;
const isTeleportDisabled = props => props && (props.disabled || props.disabled === "");
const isTeleportDeferred = props => props && (props.defer || props.defer === "");
const isTargetSVG = target => typeof SVGElement !== "undefined" && target instanceof SVGElement;
const isTargetMathML = target => typeof MathMLElement === "function" && target instanceof MathMLElement;
const resolveTarget = (props, select) => {
  const targetSelector = props && props.to;
  if (isString(targetSelector)) {
    if (!select) {
      return null;
    } else {
      const target = select(targetSelector);
      return target;
    }
  } else {
    return targetSelector;
  }
};
const TeleportImpl = {
  name: "Teleport",
  __isTeleport: true,
  process(n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized, internals) {
    const mountChildren = internals.mc,
      patchChildren = internals.pc,
      patchBlockChildren = internals.pbc,
      _internals$o = internals.o,
      insert = _internals$o.insert,
      querySelector = _internals$o.querySelector,
      createText = _internals$o.createText;
      _internals$o.createComment;
    const disabled = isTeleportDisabled(n2.props);
    let shapeFlag = n2.shapeFlag,
      children = n2.children,
      dynamicChildren = n2.dynamicChildren;
    if (n1 == null) {
      const placeholder = n2.el = createText("");
      const mainAnchor = n2.anchor = createText("");
      insert(placeholder, container, anchor);
      insert(mainAnchor, container, anchor);
      const mount = (container2, anchor2) => {
        if (shapeFlag & 16) {
          mountChildren(children, container2, anchor2, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
        }
      };
      const mountToTarget = () => {
        const target = n2.target = resolveTarget(n2.props, querySelector);
        const targetAnchor = prepareAnchor(target, n2, createText, insert);
        if (target) {
          if (namespace !== "svg" && isTargetSVG(target)) {
            namespace = "svg";
          } else if (namespace !== "mathml" && isTargetMathML(target)) {
            namespace = "mathml";
          }
          if (parentComponent && parentComponent.isCE) {
            (parentComponent.ce._teleportTargets || (parentComponent.ce._teleportTargets = /* @__PURE__ */new Set())).add(target);
          }
          if (!disabled) {
            mount(target, targetAnchor);
            updateCssVars(n2, false);
          }
        }
      };
      if (disabled) {
        mount(container, mainAnchor);
        updateCssVars(n2, true);
      }
      if (isTeleportDeferred(n2.props)) {
        n2.el.__isMounted = false;
        queuePostRenderEffect(() => {
          mountToTarget();
          delete n2.el.__isMounted;
        }, parentSuspense);
      } else {
        mountToTarget();
      }
    } else {
      if (isTeleportDeferred(n2.props) && n1.el.__isMounted === false) {
        queuePostRenderEffect(() => {
          TeleportImpl.process(n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized, internals);
        }, parentSuspense);
        return;
      }
      n2.el = n1.el;
      n2.targetStart = n1.targetStart;
      const mainAnchor = n2.anchor = n1.anchor;
      const target = n2.target = n1.target;
      const targetAnchor = n2.targetAnchor = n1.targetAnchor;
      const wasDisabled = isTeleportDisabled(n1.props);
      const currentContainer = wasDisabled ? container : target;
      const currentAnchor = wasDisabled ? mainAnchor : targetAnchor;
      if (namespace === "svg" || isTargetSVG(target)) {
        namespace = "svg";
      } else if (namespace === "mathml" || isTargetMathML(target)) {
        namespace = "mathml";
      }
      if (dynamicChildren) {
        patchBlockChildren(n1.dynamicChildren, dynamicChildren, currentContainer, parentComponent, parentSuspense, namespace, slotScopeIds);
        traverseStaticChildren(n1, n2);
      } else if (!optimized) {
        patchChildren(n1, n2, currentContainer, currentAnchor, parentComponent, parentSuspense, namespace, slotScopeIds, false);
      }
      if (disabled) {
        if (!wasDisabled) {
          moveTeleport(n2, container, mainAnchor, internals, 1);
        } else {
          if (n2.props && n1.props && n2.props.to !== n1.props.to) {
            n2.props.to = n1.props.to;
          }
        }
      } else {
        if ((n2.props && n2.props.to) !== (n1.props && n1.props.to)) {
          const nextTarget = n2.target = resolveTarget(n2.props, querySelector);
          if (nextTarget) {
            moveTeleport(n2, nextTarget, null, internals, 0);
          }
        } else if (wasDisabled) {
          moveTeleport(n2, target, targetAnchor, internals, 1);
        }
      }
      updateCssVars(n2, disabled);
    }
  },
  remove(vnode, parentComponent, parentSuspense, _ref5, doRemove) {
    let unmount = _ref5.um,
      hostRemove = _ref5.o.remove;
    const shapeFlag = vnode.shapeFlag,
      children = vnode.children,
      anchor = vnode.anchor,
      targetStart = vnode.targetStart,
      targetAnchor = vnode.targetAnchor,
      target = vnode.target,
      props = vnode.props;
    if (target) {
      hostRemove(targetStart);
      hostRemove(targetAnchor);
    }
    doRemove && hostRemove(anchor);
    if (shapeFlag & 16) {
      const shouldRemove = doRemove || !isTeleportDisabled(props);
      for (let i = 0; i < children.length; i++) {
        const child = children[i];
        unmount(child, parentComponent, parentSuspense, shouldRemove, !!child.dynamicChildren);
      }
    }
  },
  move: moveTeleport,
  hydrate: hydrateTeleport
};
function moveTeleport(vnode, container, parentAnchor, _ref6, moveType) {
  let insert = _ref6.o.insert,
    move = _ref6.m;
  if (moveType === void 0) {
    moveType = 2;
  }
  if (moveType === 0) {
    insert(vnode.targetAnchor, container, parentAnchor);
  }
  const el = vnode.el,
    anchor = vnode.anchor,
    shapeFlag = vnode.shapeFlag,
    children = vnode.children,
    props = vnode.props;
  const isReorder = moveType === 2;
  if (isReorder) {
    insert(el, container, parentAnchor);
  }
  if (!isReorder || isTeleportDisabled(props)) {
    if (shapeFlag & 16) {
      for (let i = 0; i < children.length; i++) {
        move(children[i], container, parentAnchor, 2);
      }
    }
  }
  if (isReorder) {
    insert(anchor, container, parentAnchor);
  }
}
function hydrateTeleport(node, vnode, parentComponent, parentSuspense, slotScopeIds, optimized, _ref7, hydrateChildren) {
  let _ref7$o = _ref7.o,
    nextSibling = _ref7$o.nextSibling,
    parentNode = _ref7$o.parentNode,
    querySelector = _ref7$o.querySelector,
    insert = _ref7$o.insert,
    createText = _ref7$o.createText;
  function hydrateAnchor(target2, targetNode) {
    let targetAnchor = targetNode;
    while (targetAnchor) {
      if (targetAnchor && targetAnchor.nodeType === 8) {
        if (targetAnchor.data === "teleport start anchor") {
          vnode.targetStart = targetAnchor;
        } else if (targetAnchor.data === "teleport anchor") {
          vnode.targetAnchor = targetAnchor;
          target2._lpa = vnode.targetAnchor && nextSibling(vnode.targetAnchor);
          break;
        }
      }
      targetAnchor = nextSibling(targetAnchor);
    }
  }
  function hydrateDisabledTeleport(node2, vnode2) {
    vnode2.anchor = hydrateChildren(nextSibling(node2), vnode2, parentNode(node2), parentComponent, parentSuspense, slotScopeIds, optimized);
  }
  const target = vnode.target = resolveTarget(vnode.props, querySelector);
  const disabled = isTeleportDisabled(vnode.props);
  if (target) {
    const targetNode = target._lpa || target.firstChild;
    if (vnode.shapeFlag & 16) {
      if (disabled) {
        hydrateDisabledTeleport(node, vnode);
        hydrateAnchor(target, targetNode);
        if (!vnode.targetAnchor) {
          prepareAnchor(target, vnode, createText, insert,
          // if target is the same as the main view, insert anchors before current node
          // to avoid hydrating mismatch
          parentNode(node) === target ? node : null);
        }
      } else {
        vnode.anchor = nextSibling(node);
        hydrateAnchor(target, targetNode);
        if (!vnode.targetAnchor) {
          prepareAnchor(target, vnode, createText, insert);
        }
        hydrateChildren(targetNode && nextSibling(targetNode), vnode, target, parentComponent, parentSuspense, slotScopeIds, optimized);
      }
    }
    updateCssVars(vnode, disabled);
  } else if (disabled) {
    if (vnode.shapeFlag & 16) {
      hydrateDisabledTeleport(node, vnode);
      vnode.targetStart = node;
      vnode.targetAnchor = nextSibling(node);
    }
  }
  return vnode.anchor && nextSibling(vnode.anchor);
}
const Teleport = TeleportImpl;
function updateCssVars(vnode, isDisabled) {
  const ctx = vnode.ctx;
  if (ctx && ctx.ut) {
    let node, anchor;
    if (isDisabled) {
      node = vnode.el;
      anchor = vnode.anchor;
    } else {
      node = vnode.targetStart;
      anchor = vnode.targetAnchor;
    }
    while (node && node !== anchor) {
      if (node.nodeType === 1) node.setAttribute("data-v-owner", ctx.uid);
      node = node.nextSibling;
    }
    ctx.ut();
  }
}
function prepareAnchor(target, vnode, createText, insert, anchor) {
  if (anchor === void 0) {
    anchor = null;
  }
  const targetStart = vnode.targetStart = createText("");
  const targetAnchor = vnode.targetAnchor = createText("");
  targetStart[TeleportEndKey] = targetAnchor;
  if (target) {
    insert(targetStart, target, anchor);
    insert(targetAnchor, target, anchor);
  }
  return targetAnchor;
}
const leaveCbKey = /* @__PURE__ */Symbol("_leaveCb");
function setTransitionHooks(vnode, hooks) {
  if (vnode.shapeFlag & 6 && vnode.component) {
    vnode.transition = hooks;
    setTransitionHooks(vnode.component.subTree, hooks);
  } else if (vnode.shapeFlag & 128) {
    vnode.ssContent.transition = hooks.clone(vnode.ssContent);
    vnode.ssFallback.transition = hooks.clone(vnode.ssFallback);
  } else {
    vnode.transition = hooks;
  }
}

// @__NO_SIDE_EFFECTS__
function defineComponent(options, extraOptions) {
  return isFunction(options) ?
  // #8236: extend call and options.name access are considered side-effects
  // by Rollup, so we have to wrap it in a pure-annotated IIFE.
  /* @__PURE__ */
  (() => extend$2({
    name: options.name
  }, extraOptions, {
    setup: options
  }))() : options;
}
function markAsyncBoundary(instance) {
  instance.ids = [instance.ids[0] + instance.ids[2]++ + "-", 0, 0];
}
function isTemplateRefKey(refs, key) {
  let desc;
  return !!((desc = Object.getOwnPropertyDescriptor(refs, key)) && !desc.configurable);
}
const pendingSetRefMap = /* @__PURE__ */new WeakMap();
function setRef(rawRef, oldRawRef, parentSuspense, vnode, isUnmount) {
  if (isUnmount === void 0) {
    isUnmount = false;
  }
  if (isArray(rawRef)) {
    rawRef.forEach((r, i) => setRef(r, oldRawRef && (isArray(oldRawRef) ? oldRawRef[i] : oldRawRef), parentSuspense, vnode, isUnmount));
    return;
  }
  if (isAsyncWrapper(vnode) && !isUnmount) {
    if (vnode.shapeFlag & 512 && vnode.type.__asyncResolved && vnode.component.subTree.component) {
      setRef(rawRef, oldRawRef, parentSuspense, vnode.component.subTree);
    }
    return;
  }
  const refValue = vnode.shapeFlag & 4 ? getComponentPublicInstance(vnode.component) : vnode.el;
  const value = isUnmount ? null : refValue;
  const owner = rawRef.i,
    ref = rawRef.r;
  const oldRef = oldRawRef && oldRawRef.r;
  const refs = owner.refs === EMPTY_OBJ ? owner.refs = {} : owner.refs;
  const setupState = owner.setupState;
  const rawSetupState = toRaw(setupState);
  const canSetSetupRef = setupState === EMPTY_OBJ ? NO : key => {
    if (isTemplateRefKey(refs, key)) {
      return false;
    }
    return hasOwn(rawSetupState, key);
  };
  const canSetRef = (ref2, key) => {
    if (key && isTemplateRefKey(refs, key)) {
      return false;
    }
    return true;
  };
  if (oldRef != null && oldRef !== ref) {
    invalidatePendingSetRef(oldRawRef);
    if (isString(oldRef)) {
      refs[oldRef] = null;
      if (canSetSetupRef(oldRef)) {
        setupState[oldRef] = null;
      }
    } else if (isRef(oldRef)) {
      const oldRawRefAtom = oldRawRef;
      if (canSetRef(oldRef, oldRawRefAtom.k)) {
        oldRef.value = null;
      }
      if (oldRawRefAtom.k) refs[oldRawRefAtom.k] = null;
    }
  }
  if (isFunction(ref)) {
    callWithErrorHandling(ref, owner, 12, [value, refs]);
  } else {
    const _isString = isString(ref);
    const _isRef = isRef(ref);
    if (_isString || _isRef) {
      const doSet = () => {
        if (rawRef.f) {
          const existing = _isString ? canSetSetupRef(ref) ? setupState[ref] : refs[ref] : canSetRef() || !rawRef.k ? ref.value : refs[rawRef.k];
          if (isUnmount) {
            isArray(existing) && remove$2(existing, refValue);
          } else {
            if (!isArray(existing)) {
              if (_isString) {
                refs[ref] = [refValue];
                if (canSetSetupRef(ref)) {
                  setupState[ref] = refs[ref];
                }
              } else {
                const newVal = [refValue];
                if (canSetRef(ref, rawRef.k)) {
                  ref.value = newVal;
                }
                if (rawRef.k) refs[rawRef.k] = newVal;
              }
            } else if (!existing.includes(refValue)) {
              existing.push(refValue);
            }
          }
        } else if (_isString) {
          refs[ref] = value;
          if (canSetSetupRef(ref)) {
            setupState[ref] = value;
          }
        } else if (_isRef) {
          if (canSetRef(ref, rawRef.k)) {
            ref.value = value;
          }
          if (rawRef.k) refs[rawRef.k] = value;
        } else ;
      };
      if (value) {
        const job = () => {
          doSet();
          pendingSetRefMap.delete(rawRef);
        };
        job.id = -1;
        pendingSetRefMap.set(rawRef, job);
        queuePostRenderEffect(job, parentSuspense);
      } else {
        invalidatePendingSetRef(rawRef);
        doSet();
      }
    }
  }
}
function invalidatePendingSetRef(rawRef) {
  const pendingSetRef = pendingSetRefMap.get(rawRef);
  if (pendingSetRef) {
    pendingSetRef.flags |= 8;
    pendingSetRefMap.delete(rawRef);
  }
}
getGlobalThis().requestIdleCallback || (cb => setTimeout(cb, 1));
getGlobalThis().cancelIdleCallback || (id => clearTimeout(id));
const isAsyncWrapper = i => !!i.type.__asyncLoader;
const isKeepAlive = vnode => vnode.type.__isKeepAlive;
function onActivated(hook, target) {
  registerKeepAliveHook(hook, "a", target);
}
function onDeactivated(hook, target) {
  registerKeepAliveHook(hook, "da", target);
}
function registerKeepAliveHook(hook, type, target) {
  if (target === void 0) {
    target = currentInstance;
  }
  const wrappedHook = hook.__wdc || (hook.__wdc = () => {
    let current = target;
    while (current) {
      if (current.isDeactivated) {
        return;
      }
      current = current.parent;
    }
    return hook();
  });
  injectHook(type, wrappedHook, target);
  if (target) {
    let current = target.parent;
    while (current && current.parent) {
      if (isKeepAlive(current.parent.vnode)) {
        injectToKeepAliveRoot(wrappedHook, type, target, current);
      }
      current = current.parent;
    }
  }
}
function injectToKeepAliveRoot(hook, type, target, keepAliveRoot) {
  const injected = injectHook(type, hook, keepAliveRoot, true
  /* prepend */);
  onUnmounted(() => {
    remove$2(keepAliveRoot[type], injected);
  }, target);
}
function injectHook(type, hook, target, prepend) {
  if (target === void 0) {
    target = currentInstance;
  }
  if (prepend === void 0) {
    prepend = false;
  }
  if (target) {
    const hooks = target[type] || (target[type] = []);
    const wrappedHook = hook.__weh || (hook.__weh = function () {
      pauseTracking();
      const reset = setCurrentInstance(target);
      for (var _len3 = arguments.length, args = new Array(_len3), _key3 = 0; _key3 < _len3; _key3++) {
        args[_key3] = arguments[_key3];
      }
      const res = callWithAsyncErrorHandling(hook, target, type, args);
      reset();
      resetTracking();
      return res;
    });
    if (prepend) {
      hooks.unshift(wrappedHook);
    } else {
      hooks.push(wrappedHook);
    }
    return wrappedHook;
  }
}
const createHook = lifecycle => function (hook, target) {
  if (target === void 0) {
    target = currentInstance;
  }
  if (!isInSSRComponentSetup || lifecycle === "sp") {
    injectHook(lifecycle, function () {
      return hook(...arguments);
    }, target);
  }
};
const onBeforeMount = createHook("bm");
const onMounted = createHook("m");
const onBeforeUpdate = createHook("bu");
const onUpdated = createHook("u");
const onBeforeUnmount = createHook("bum");
const onUnmounted = createHook("um");
const onServerPrefetch = createHook("sp");
const onRenderTriggered = createHook("rtg");
const onRenderTracked = createHook("rtc");
function onErrorCaptured(hook, target) {
  if (target === void 0) {
    target = currentInstance;
  }
  injectHook("ec", hook, target);
}
const COMPONENTS = "components";
function resolveComponent(name, maybeSelfReference) {
  return resolveAsset(COMPONENTS, name, true, maybeSelfReference) || name;
}
const NULL_DYNAMIC_COMPONENT = /* @__PURE__ */Symbol.for("v-ndc");
function resolveDynamicComponent(component) {
  if (isString(component)) {
    return resolveAsset(COMPONENTS, component) || component;
  } else {
    return component || NULL_DYNAMIC_COMPONENT;
  }
}
function resolveAsset(type, name, warnMissing, maybeSelfReference) {
  if (maybeSelfReference === void 0) {
    maybeSelfReference = false;
  }
  const instance = currentRenderingInstance || currentInstance;
  if (instance) {
    const Component = instance.type;
    {
      const selfName = getComponentName(Component, false);
      if (selfName && (selfName === name || selfName === camelize(name) || selfName === capitalize(camelize(name)))) {
        return Component;
      }
    }
    const res =
    // local registration
    // check instance[type] first which is resolved for options API
    resolve(instance[type] || Component[type], name) ||
    // global registration
    resolve(instance.appContext[type], name);
    if (!res && maybeSelfReference) {
      return Component;
    }
    return res;
  }
}
function resolve(registry, name) {
  return registry && (registry[name] || registry[camelize(name)] || registry[capitalize(camelize(name))]);
}
function renderList(source, renderItem, cache, index) {
  let ret;
  const cached = cache && cache[index];
  const sourceIsArray = isArray(source);
  if (sourceIsArray || isString(source)) {
    const sourceIsReactiveArray = sourceIsArray && isReactive(source);
    let needsWrap = false;
    let isReadonlySource = false;
    if (sourceIsReactiveArray) {
      needsWrap = !isShallow(source);
      isReadonlySource = isReadonly(source);
      source = shallowReadArray(source);
    }
    ret = new Array(source.length);
    for (let i = 0, l = source.length; i < l; i++) {
      ret[i] = renderItem(needsWrap ? isReadonlySource ? toReadonly(toReactive(source[i])) : toReactive(source[i]) : source[i], i, void 0, cached && cached[i]);
    }
  } else if (typeof source === "number") {
    {
      ret = new Array(source);
      for (let i = 0; i < source; i++) {
        ret[i] = renderItem(i + 1, i, void 0, cached && cached[i]);
      }
    }
  } else if (isObject$2(source)) {
    if (source[Symbol.iterator]) {
      ret = Array.from(source, (item, i) => renderItem(item, i, void 0, cached && cached[i]));
    } else {
      const keys = Object.keys(source);
      ret = new Array(keys.length);
      for (let i = 0, l = keys.length; i < l; i++) {
        const key = keys[i];
        ret[i] = renderItem(source[key], key, i, cached && cached[i]);
      }
    }
  } else {
    ret = [];
  }
  if (cache) {
    cache[index] = ret;
  }
  return ret;
}
function renderSlot(slots, name, props, fallback, noSlotted) {
  if (props === void 0) {
    props = {};
  }
  if (currentRenderingInstance.ce || currentRenderingInstance.parent && isAsyncWrapper(currentRenderingInstance.parent) && currentRenderingInstance.parent.ce) {
    const hasProps = Object.keys(props).length > 0;
    if (name !== "default") props.name = name;
    return openBlock(), createBlock(Fragment, null, [createVNode("slot", props, fallback && fallback())], hasProps ? -2 : 64);
  }
  let slot = slots[name];
  if (slot && slot._c) {
    slot._d = false;
  }
  openBlock();
  const validSlotContent = slot && ensureValidVNode(slot(props));
  const slotKey = props.key ||
  // slot content array of a dynamic conditional slot may have a branch
  // key attached in the `createSlots` helper, respect that
  validSlotContent && validSlotContent.key;
  const rendered = createBlock(Fragment, {
    key: (slotKey && !isSymbol(slotKey) ? slotKey : "_" + name) + (
    // #7256 force differentiate fallback content from actual content
    !validSlotContent && fallback ? "_fb" : "")
  }, validSlotContent || (fallback ? fallback() : []), validSlotContent && slots._ === 1 ? 64 : -2);
  if (rendered.scopeId) {
    rendered.slotScopeIds = [rendered.scopeId + "-s"];
  }
  if (slot && slot._c) {
    slot._d = true;
  }
  return rendered;
}
function ensureValidVNode(vnodes) {
  return vnodes.some(child => {
    if (!isVNode(child)) return true;
    if (child.type === Comment) return false;
    if (child.type === Fragment && !ensureValidVNode(child.children)) return false;
    return true;
  }) ? vnodes : null;
}
const getPublicInstance = i => {
  if (!i) return null;
  if (isStatefulComponent(i)) return getComponentPublicInstance(i);
  return getPublicInstance(i.parent);
};
const publicPropertiesMap =
// Move PURE marker to new line to workaround compiler discarding it
// due to type annotation
/* @__PURE__ */
extend$2(/* @__PURE__ */Object.create(null), {
  $: i => i,
  $el: i => i.vnode.el,
  $data: i => i.data,
  $props: i => i.props,
  $attrs: i => i.attrs,
  $slots: i => i.slots,
  $refs: i => i.refs,
  $parent: i => getPublicInstance(i.parent),
  $root: i => getPublicInstance(i.root),
  $host: i => i.ce,
  $emit: i => i.emit,
  $options: i => resolveMergedOptions(i) ,
  $forceUpdate: i => i.f || (i.f = () => {
    queueJob(i.update);
  }),
  $nextTick: i => i.n || (i.n = nextTick.bind(i.proxy)),
  $watch: i => instanceWatch.bind(i) 
});
const hasSetupBinding = (state, key) => state !== EMPTY_OBJ && !state.__isScriptSetup && hasOwn(state, key);
const PublicInstanceProxyHandlers = {
  get(_ref11, key) {
    let instance = _ref11._;
    if (key === "__v_skip") {
      return true;
    }
    const ctx = instance.ctx,
      setupState = instance.setupState,
      data = instance.data,
      props = instance.props,
      accessCache = instance.accessCache,
      type = instance.type,
      appContext = instance.appContext;
    if (key[0] !== "$") {
      const n = accessCache[key];
      if (n !== void 0) {
        switch (n) {
          case 1 /* SETUP */:
            return setupState[key];
          case 2 /* DATA */:
            return data[key];
          case 4 /* CONTEXT */:
            return ctx[key];
          case 3 /* PROPS */:
            return props[key];
        }
      } else if (hasSetupBinding(setupState, key)) {
        accessCache[key] = 1 /* SETUP */;
        return setupState[key];
      } else if (data !== EMPTY_OBJ && hasOwn(data, key)) {
        accessCache[key] = 2 /* DATA */;
        return data[key];
      } else if (hasOwn(props, key)) {
        accessCache[key] = 3 /* PROPS */;
        return props[key];
      } else if (ctx !== EMPTY_OBJ && hasOwn(ctx, key)) {
        accessCache[key] = 4 /* CONTEXT */;
        return ctx[key];
      } else if (shouldCacheAccess) {
        accessCache[key] = 0 /* OTHER */;
      }
    }
    const publicGetter = publicPropertiesMap[key];
    let cssModule, globalProperties;
    if (publicGetter) {
      if (key === "$attrs") {
        track(instance.attrs, "get", "");
      }
      return publicGetter(instance);
    } else if (
    // css module (injected by vue-loader)
    (cssModule = type.__cssModules) && (cssModule = cssModule[key])) {
      return cssModule;
    } else if (ctx !== EMPTY_OBJ && hasOwn(ctx, key)) {
      accessCache[key] = 4 /* CONTEXT */;
      return ctx[key];
    } else if (
    // global properties
    globalProperties = appContext.config.globalProperties, hasOwn(globalProperties, key)) {
      {
        return globalProperties[key];
      }
    } else ;
  },
  set(_ref12, key, value) {
    let instance = _ref12._;
    const data = instance.data,
      setupState = instance.setupState,
      ctx = instance.ctx;
    if (hasSetupBinding(setupState, key)) {
      setupState[key] = value;
      return true;
    } else if (data !== EMPTY_OBJ && hasOwn(data, key)) {
      data[key] = value;
      return true;
    } else if (hasOwn(instance.props, key)) {
      return false;
    }
    if (key[0] === "$" && key.slice(1) in instance) {
      return false;
    } else {
      {
        ctx[key] = value;
      }
    }
    return true;
  },
  has(_ref13, key) {
    let _ref13$_ = _ref13._,
      data = _ref13$_.data,
      setupState = _ref13$_.setupState,
      accessCache = _ref13$_.accessCache,
      ctx = _ref13$_.ctx,
      appContext = _ref13$_.appContext,
      props = _ref13$_.props,
      type = _ref13$_.type;
    let cssModules;
    return !!(accessCache[key] || data !== EMPTY_OBJ && key[0] !== "$" && hasOwn(data, key) || hasSetupBinding(setupState, key) || hasOwn(props, key) || hasOwn(ctx, key) || hasOwn(publicPropertiesMap, key) || hasOwn(appContext.config.globalProperties, key) || (cssModules = type.__cssModules) && cssModules[key]);
  },
  defineProperty(target, key, descriptor) {
    if (descriptor.get != null) {
      target._.accessCache[key] = 0;
    } else if (hasOwn(descriptor, "value")) {
      this.set(target, key, descriptor.value, null);
    }
    return Reflect.defineProperty(target, key, descriptor);
  }
};
function useSlots() {
  return getContext().slots;
}
function useAttrs() {
  return getContext().attrs;
}
function getContext(calledFunctionName) {
  const i = getCurrentInstance();
  return i.setupContext || (i.setupContext = createSetupContext(i));
}
function normalizePropsOrEmits(props) {
  return isArray(props) ? props.reduce((normalized, p) => (normalized[p] = null, normalized), {}) : props;
}
function createPropsRestProxy(props, excludedKeys) {
  const ret = {};
  for (const key in props) {
    if (!excludedKeys.includes(key)) {
      Object.defineProperty(ret, key, {
        enumerable: true,
        get: () => props[key]
      });
    }
  }
  return ret;
}
let shouldCacheAccess = true;
function applyOptions(instance) {
  const options = resolveMergedOptions(instance);
  const publicThis = instance.proxy;
  const ctx = instance.ctx;
  shouldCacheAccess = false;
  if (options.beforeCreate) {
    callHook(options.beforeCreate, instance, "bc");
  }
  const dataOptions = options.data,
    computedOptions = options.computed,
    methods = options.methods,
    watchOptions = options.watch,
    provideOptions = options.provide,
    injectOptions = options.inject,
    created = options.created,
    beforeMount = options.beforeMount,
    mounted = options.mounted,
    beforeUpdate = options.beforeUpdate,
    updated = options.updated,
    activated = options.activated,
    deactivated = options.deactivated;
    options.beforeDestroy;
    const beforeUnmount = options.beforeUnmount;
    options.destroyed;
    const unmounted = options.unmounted,
    render = options.render,
    renderTracked = options.renderTracked,
    renderTriggered = options.renderTriggered,
    errorCaptured = options.errorCaptured,
    serverPrefetch = options.serverPrefetch,
    expose = options.expose,
    inheritAttrs = options.inheritAttrs,
    components = options.components,
    directives = options.directives;
    options.filters;
  if (injectOptions) {
    resolveInjections(injectOptions, ctx);
  }
  if (methods) {
    for (const key in methods) {
      const methodHandler = methods[key];
      if (isFunction(methodHandler)) {
        {
          ctx[key] = methodHandler.bind(publicThis);
        }
      }
    }
  }
  if (dataOptions) {
    const data = dataOptions.call(publicThis, publicThis);
    if (!isObject$2(data)) ; else {
      instance.data = reactive(data);
    }
  }
  shouldCacheAccess = true;
  if (computedOptions) {
    for (const key in computedOptions) {
      const opt = computedOptions[key];
      const get = isFunction(opt) ? opt.bind(publicThis, publicThis) : isFunction(opt.get) ? opt.get.bind(publicThis, publicThis) : NOOP;
      const set = !isFunction(opt) && isFunction(opt.set) ? opt.set.bind(publicThis) : NOOP;
      const c = computed({
        get,
        set
      });
      Object.defineProperty(ctx, key, {
        enumerable: true,
        configurable: true,
        get: () => c.value,
        set: v => c.value = v
      });
    }
  }
  if (watchOptions) {
    for (const key in watchOptions) {
      createWatcher(watchOptions[key], ctx, publicThis, key);
    }
  }
  if (provideOptions) {
    const provides = isFunction(provideOptions) ? provideOptions.call(publicThis) : provideOptions;
    Reflect.ownKeys(provides).forEach(key => {
      provide(key, provides[key]);
    });
  }
  if (created) {
    callHook(created, instance, "c");
  }
  function registerLifecycleHook(register, hook) {
    if (isArray(hook)) {
      hook.forEach(_hook => register(_hook.bind(publicThis)));
    } else if (hook) {
      register(hook.bind(publicThis));
    }
  }
  registerLifecycleHook(onBeforeMount, beforeMount);
  registerLifecycleHook(onMounted, mounted);
  registerLifecycleHook(onBeforeUpdate, beforeUpdate);
  registerLifecycleHook(onUpdated, updated);
  registerLifecycleHook(onActivated, activated);
  registerLifecycleHook(onDeactivated, deactivated);
  registerLifecycleHook(onErrorCaptured, errorCaptured);
  registerLifecycleHook(onRenderTracked, renderTracked);
  registerLifecycleHook(onRenderTriggered, renderTriggered);
  registerLifecycleHook(onBeforeUnmount, beforeUnmount);
  registerLifecycleHook(onUnmounted, unmounted);
  registerLifecycleHook(onServerPrefetch, serverPrefetch);
  if (isArray(expose)) {
    if (expose.length) {
      const exposed = instance.exposed || (instance.exposed = {});
      expose.forEach(key => {
        Object.defineProperty(exposed, key, {
          get: () => publicThis[key],
          set: val => publicThis[key] = val,
          enumerable: true
        });
      });
    } else if (!instance.exposed) {
      instance.exposed = {};
    }
  }
  if (render && instance.render === NOOP) {
    instance.render = render;
  }
  if (inheritAttrs != null) {
    instance.inheritAttrs = inheritAttrs;
  }
  if (components) instance.components = components;
  if (directives) instance.directives = directives;
  if (serverPrefetch) {
    markAsyncBoundary(instance);
  }
}
function resolveInjections(injectOptions, ctx, checkDuplicateProperties) {
  if (isArray(injectOptions)) {
    injectOptions = normalizeInject(injectOptions);
  }
  for (const key in injectOptions) {
    const opt = injectOptions[key];
    let injected;
    if (isObject$2(opt)) {
      if ("default" in opt) {
        injected = inject(opt.from || key, opt.default, true);
      } else {
        injected = inject(opt.from || key);
      }
    } else {
      injected = inject(opt);
    }
    if (isRef(injected)) {
      Object.defineProperty(ctx, key, {
        enumerable: true,
        configurable: true,
        get: () => injected.value,
        set: v => injected.value = v
      });
    } else {
      ctx[key] = injected;
    }
  }
}
function callHook(hook, instance, type) {
  callWithAsyncErrorHandling(isArray(hook) ? hook.map(h => h.bind(instance.proxy)) : hook.bind(instance.proxy), instance, type);
}
function createWatcher(raw, ctx, publicThis, key) {
  let getter = key.includes(".") ? createPathGetter(publicThis, key) : () => publicThis[key];
  if (isString(raw)) {
    const handler = ctx[raw];
    if (isFunction(handler)) {
      {
        watch(getter, handler);
      }
    }
  } else if (isFunction(raw)) {
    {
      watch(getter, raw.bind(publicThis));
    }
  } else if (isObject$2(raw)) {
    if (isArray(raw)) {
      raw.forEach(r => createWatcher(r, ctx, publicThis, key));
    } else {
      const handler = isFunction(raw.handler) ? raw.handler.bind(publicThis) : ctx[raw.handler];
      if (isFunction(handler)) {
        watch(getter, handler, raw);
      }
    }
  } else ;
}
function resolveMergedOptions(instance) {
  const base = instance.type;
  const mixins = base.mixins,
    extendsOptions = base.extends;
  const _instance$appContext = instance.appContext,
    globalMixins = _instance$appContext.mixins,
    cache = _instance$appContext.optionsCache,
    optionMergeStrategies = _instance$appContext.config.optionMergeStrategies;
  const cached = cache.get(base);
  let resolved;
  if (cached) {
    resolved = cached;
  } else if (!globalMixins.length && !mixins && !extendsOptions) {
    {
      resolved = base;
    }
  } else {
    resolved = {};
    if (globalMixins.length) {
      globalMixins.forEach(m => mergeOptions(resolved, m, optionMergeStrategies, true));
    }
    mergeOptions(resolved, base, optionMergeStrategies);
  }
  if (isObject$2(base)) {
    cache.set(base, resolved);
  }
  return resolved;
}
function mergeOptions(to, from, strats, asMixin) {
  if (asMixin === void 0) {
    asMixin = false;
  }
  const mixins = from.mixins,
    extendsOptions = from.extends;
  if (extendsOptions) {
    mergeOptions(to, extendsOptions, strats, true);
  }
  if (mixins) {
    mixins.forEach(m => mergeOptions(to, m, strats, true));
  }
  for (const key in from) {
    if (asMixin && key === "expose") ; else {
      const strat = internalOptionMergeStrats[key] || strats && strats[key];
      to[key] = strat ? strat(to[key], from[key]) : from[key];
    }
  }
  return to;
}
const internalOptionMergeStrats = {
  data: mergeDataFn,
  props: mergeEmitsOrPropsOptions,
  emits: mergeEmitsOrPropsOptions,
  // objects
  methods: mergeObjectOptions,
  computed: mergeObjectOptions,
  // lifecycle
  beforeCreate: mergeAsArray,
  created: mergeAsArray,
  beforeMount: mergeAsArray,
  mounted: mergeAsArray,
  beforeUpdate: mergeAsArray,
  updated: mergeAsArray,
  beforeDestroy: mergeAsArray,
  beforeUnmount: mergeAsArray,
  destroyed: mergeAsArray,
  unmounted: mergeAsArray,
  activated: mergeAsArray,
  deactivated: mergeAsArray,
  errorCaptured: mergeAsArray,
  serverPrefetch: mergeAsArray,
  // assets
  components: mergeObjectOptions,
  directives: mergeObjectOptions,
  // watch
  watch: mergeWatchOptions,
  // provide / inject
  provide: mergeDataFn,
  inject: mergeInject
};
function mergeDataFn(to, from) {
  if (!from) {
    return to;
  }
  if (!to) {
    return from;
  }
  return function mergedDataFn() {
    return extend$2(isFunction(to) ? to.call(this, this) : to, isFunction(from) ? from.call(this, this) : from);
  };
}
function mergeInject(to, from) {
  return mergeObjectOptions(normalizeInject(to), normalizeInject(from));
}
function normalizeInject(raw) {
  if (isArray(raw)) {
    const res = {};
    for (let i = 0; i < raw.length; i++) {
      res[raw[i]] = raw[i];
    }
    return res;
  }
  return raw;
}
function mergeAsArray(to, from) {
  return to ? [...new Set([].concat(to, from))] : from;
}
function mergeObjectOptions(to, from) {
  return to ? extend$2(/* @__PURE__ */Object.create(null), to, from) : from;
}
function mergeEmitsOrPropsOptions(to, from) {
  if (to) {
    if (isArray(to) && isArray(from)) {
      return [... /* @__PURE__ */new Set([...to, ...from])];
    }
    return extend$2(/* @__PURE__ */Object.create(null), normalizePropsOrEmits(to), normalizePropsOrEmits(from != null ? from : {}));
  } else {
    return from;
  }
}
function mergeWatchOptions(to, from) {
  if (!to) return from;
  if (!from) return to;
  const merged = extend$2(/* @__PURE__ */Object.create(null), to);
  for (const key in from) {
    merged[key] = mergeAsArray(to[key], from[key]);
  }
  return merged;
}
function createAppContext() {
  return {
    app: null,
    config: {
      isNativeTag: NO,
      performance: false,
      globalProperties: {},
      optionMergeStrategies: {},
      errorHandler: void 0,
      warnHandler: void 0,
      compilerOptions: {}
    },
    mixins: [],
    components: {},
    directives: {},
    provides: /* @__PURE__ */Object.create(null),
    optionsCache: /* @__PURE__ */new WeakMap(),
    propsCache: /* @__PURE__ */new WeakMap(),
    emitsCache: /* @__PURE__ */new WeakMap()
  };
}
let uid$1 = 0;
function createAppAPI(render, hydrate) {
  return function createApp(rootComponent, rootProps) {
    if (rootProps === void 0) {
      rootProps = null;
    }
    if (!isFunction(rootComponent)) {
      rootComponent = extend$2({}, rootComponent);
    }
    if (rootProps != null && !isObject$2(rootProps)) {
      rootProps = null;
    }
    const context = createAppContext();
    const installedPlugins = /* @__PURE__ */new WeakSet();
    const pluginCleanupFns = [];
    let isMounted = false;
    const app = context.app = {
      _uid: uid$1++,
      _component: rootComponent,
      _props: rootProps,
      _container: null,
      _context: context,
      _instance: null,
      version,
      get config() {
        return context.config;
      },
      set config(v) {
      },
      use(plugin) {
        for (var _len4 = arguments.length, options = new Array(_len4 > 1 ? _len4 - 1 : 0), _key4 = 1; _key4 < _len4; _key4++) {
          options[_key4 - 1] = arguments[_key4];
        }
        if (installedPlugins.has(plugin)) ; else if (plugin && isFunction(plugin.install)) {
          installedPlugins.add(plugin);
          plugin.install(app, ...options);
        } else if (isFunction(plugin)) {
          installedPlugins.add(plugin);
          plugin(app, ...options);
        } else ;
        return app;
      },
      mixin(mixin) {
        {
          if (!context.mixins.includes(mixin)) {
            context.mixins.push(mixin);
          }
        }
        return app;
      },
      component(name, component) {
        if (!component) {
          return context.components[name];
        }
        context.components[name] = component;
        return app;
      },
      directive(name, directive) {
        if (!directive) {
          return context.directives[name];
        }
        context.directives[name] = directive;
        return app;
      },
      mount(rootContainer, isHydrate, namespace) {
        if (!isMounted) {
          const vnode = app._ceVNode || createVNode(rootComponent, rootProps);
          vnode.appContext = context;
          if (namespace === true) {
            namespace = "svg";
          } else if (namespace === false) {
            namespace = void 0;
          }
          {
            render(vnode, rootContainer, namespace);
          }
          isMounted = true;
          app._container = rootContainer;
          rootContainer.__vue_app__ = app;
          return getComponentPublicInstance(vnode.component);
        }
      },
      onUnmount(cleanupFn) {
        pluginCleanupFns.push(cleanupFn);
      },
      unmount() {
        if (isMounted) {
          callWithAsyncErrorHandling(pluginCleanupFns, app._instance, 16);
          render(null, app._container);
          delete app._container.__vue_app__;
        }
      },
      provide(key, value) {
        context.provides[key] = value;
        return app;
      },
      runWithContext(fn) {
        const lastApp = currentApp;
        currentApp = app;
        try {
          return fn();
        } finally {
          currentApp = lastApp;
        }
      }
    };
    return app;
  };
}
let currentApp = null;
const getModelModifiers = (props, modelName) => {
  return modelName === "modelValue" || modelName === "model-value" ? props.modelModifiers : props[modelName + "Modifiers"] || props[camelize(modelName) + "Modifiers"] || props[hyphenate(modelName) + "Modifiers"];
};
function emit(instance, event) {
  if (instance.isUnmounted) return;
  const props = instance.vnode.props || EMPTY_OBJ;
  for (var _len5 = arguments.length, rawArgs = new Array(_len5 > 2 ? _len5 - 2 : 0), _key5 = 2; _key5 < _len5; _key5++) {
    rawArgs[_key5 - 2] = arguments[_key5];
  }
  let args = rawArgs;
  const isModelListener = event.startsWith("update:");
  const modifiers = isModelListener && getModelModifiers(props, event.slice(7));
  if (modifiers) {
    if (modifiers.trim) {
      args = rawArgs.map(a => isString(a) ? a.trim() : a);
    }
    if (modifiers.number) {
      args = rawArgs.map(looseToNumber);
    }
  }
  let handlerName;
  let handler = props[handlerName = toHandlerKey$1(event)] ||
  // also try camelCase event handler (#2249)
  props[handlerName = toHandlerKey$1(camelize(event))];
  if (!handler && isModelListener) {
    handler = props[handlerName = toHandlerKey$1(hyphenate(event))];
  }
  if (handler) {
    callWithAsyncErrorHandling(handler, instance, 6, args);
  }
  const onceHandler = props[handlerName + "Once"];
  if (onceHandler) {
    if (!instance.emitted) {
      instance.emitted = {};
    } else if (instance.emitted[handlerName]) {
      return;
    }
    instance.emitted[handlerName] = true;
    callWithAsyncErrorHandling(onceHandler, instance, 6, args);
  }
}
const mixinEmitsCache = /* @__PURE__ */new WeakMap();
function normalizeEmitsOptions(comp, appContext, asMixin) {
  if (asMixin === void 0) {
    asMixin = false;
  }
  const cache = asMixin ? mixinEmitsCache : appContext.emitsCache;
  const cached = cache.get(comp);
  if (cached !== void 0) {
    return cached;
  }
  const raw = comp.emits;
  let normalized = {};
  let hasExtends = false;
  if (!isFunction(comp)) {
    const extendEmits = raw2 => {
      const normalizedFromExtend = normalizeEmitsOptions(raw2, appContext, true);
      if (normalizedFromExtend) {
        hasExtends = true;
        extend$2(normalized, normalizedFromExtend);
      }
    };
    if (!asMixin && appContext.mixins.length) {
      appContext.mixins.forEach(extendEmits);
    }
    if (comp.extends) {
      extendEmits(comp.extends);
    }
    if (comp.mixins) {
      comp.mixins.forEach(extendEmits);
    }
  }
  if (!raw && !hasExtends) {
    if (isObject$2(comp)) {
      cache.set(comp, null);
    }
    return null;
  }
  if (isArray(raw)) {
    raw.forEach(key => normalized[key] = null);
  } else {
    extend$2(normalized, raw);
  }
  if (isObject$2(comp)) {
    cache.set(comp, normalized);
  }
  return normalized;
}
function isEmitListener(options, key) {
  if (!options || !isOn(key)) {
    return false;
  }
  key = key.slice(2).replace(/Once$/, "");
  return hasOwn(options, key[0].toLowerCase() + key.slice(1)) || hasOwn(options, hyphenate(key)) || hasOwn(options, key);
}
function markAttrsAccessed() {
}
function renderComponentRoot(instance) {
  const Component = instance.type,
    vnode = instance.vnode,
    proxy = instance.proxy,
    withProxy = instance.withProxy,
    _instance$propsOption4 = instance.propsOptions,
    propsOptions = _instance$propsOption4[0],
    slots = instance.slots,
    attrs = instance.attrs,
    emit = instance.emit,
    render = instance.render,
    renderCache = instance.renderCache,
    props = instance.props,
    data = instance.data,
    setupState = instance.setupState,
    ctx = instance.ctx,
    inheritAttrs = instance.inheritAttrs;
  const prev = setCurrentRenderingInstance(instance);
  let result;
  let fallthroughAttrs;
  try {
    if (vnode.shapeFlag & 4) {
      const proxyToUse = withProxy || proxy;
      const thisProxy = !!("production" !== "production") && setupState.__isScriptSetup ? new Proxy(proxyToUse, {
        get(target, key, receiver) {
          warn$1("Property '" + String(key) + "' was accessed via 'this'. Avoid using 'this' in templates.");
          return Reflect.get(target, key, receiver);
        }
      }) : proxyToUse;
      result = normalizeVNode(render.call(thisProxy, proxyToUse, renderCache, !!("production" !== "production") ? shallowReadonly(props) : props, setupState, data, ctx));
      fallthroughAttrs = attrs;
    } else {
      const render2 = Component;
      if (!!("production" !== "production") && attrs === props) ;
      result = normalizeVNode(render2.length > 1 ? render2(!!("production" !== "production") ? shallowReadonly(props) : props, !!("production" !== "production") ? {
        get attrs() {
          markAttrsAccessed();
          return shallowReadonly(attrs);
        },
        slots,
        emit
      } : {
        attrs,
        slots,
        emit
      }) : render2(!!("production" !== "production") ? shallowReadonly(props) : props, null));
      fallthroughAttrs = Component.props ? attrs : getFunctionalFallthrough(attrs);
    }
  } catch (err) {
    blockStack.length = 0;
    handleError(err, instance, 1);
    result = createVNode(Comment);
  }
  let root = result;
  if (fallthroughAttrs && inheritAttrs !== false) {
    const keys = Object.keys(fallthroughAttrs);
    const _root = root,
      shapeFlag = _root.shapeFlag;
    if (keys.length) {
      if (shapeFlag & (1 | 6)) {
        if (propsOptions && keys.some(isModelListener)) {
          fallthroughAttrs = filterModelListeners(fallthroughAttrs, propsOptions);
        }
        root = cloneVNode(root, fallthroughAttrs, false, true);
      }
    }
  }
  if (vnode.dirs) {
    root = cloneVNode(root, null, false, true);
    root.dirs = root.dirs ? root.dirs.concat(vnode.dirs) : vnode.dirs;
  }
  if (vnode.transition) {
    setTransitionHooks(root, vnode.transition);
  }
  {
    result = root;
  }
  setCurrentRenderingInstance(prev);
  return result;
}
const getFunctionalFallthrough = attrs => {
  let res;
  for (const key in attrs) {
    if (key === "class" || key === "style" || isOn(key)) {
      (res || (res = {}))[key] = attrs[key];
    }
  }
  return res;
};
const filterModelListeners = (attrs, props) => {
  const res = {};
  for (const key in attrs) {
    if (!isModelListener(key) || !(key.slice(9) in props)) {
      res[key] = attrs[key];
    }
  }
  return res;
};
function shouldUpdateComponent(prevVNode, nextVNode, optimized) {
  const prevProps = prevVNode.props,
    prevChildren = prevVNode.children,
    component = prevVNode.component;
  const nextProps = nextVNode.props,
    nextChildren = nextVNode.children,
    patchFlag = nextVNode.patchFlag;
  const emits = component.emitsOptions;
  if (nextVNode.dirs || nextVNode.transition) {
    return true;
  }
  if (optimized && patchFlag >= 0) {
    if (patchFlag & 1024) {
      return true;
    }
    if (patchFlag & 16) {
      if (!prevProps) {
        return !!nextProps;
      }
      return hasPropsChanged(prevProps, nextProps, emits);
    } else if (patchFlag & 8) {
      const dynamicProps = nextVNode.dynamicProps;
      for (let i = 0; i < dynamicProps.length; i++) {
        const key = dynamicProps[i];
        if (hasPropValueChanged(nextProps, prevProps, key) && !isEmitListener(emits, key)) {
          return true;
        }
      }
    }
  } else {
    if (prevChildren || nextChildren) {
      if (!nextChildren || !nextChildren.$stable) {
        return true;
      }
    }
    if (prevProps === nextProps) {
      return false;
    }
    if (!prevProps) {
      return !!nextProps;
    }
    if (!nextProps) {
      return true;
    }
    return hasPropsChanged(prevProps, nextProps, emits);
  }
  return false;
}
function hasPropsChanged(prevProps, nextProps, emitsOptions) {
  const nextKeys = Object.keys(nextProps);
  if (nextKeys.length !== Object.keys(prevProps).length) {
    return true;
  }
  for (let i = 0; i < nextKeys.length; i++) {
    const key = nextKeys[i];
    if (hasPropValueChanged(nextProps, prevProps, key) && !isEmitListener(emitsOptions, key)) {
      return true;
    }
  }
  return false;
}
function hasPropValueChanged(nextProps, prevProps, key) {
  const nextProp = nextProps[key];
  const prevProp = prevProps[key];
  if (key === "style" && isObject$2(nextProp) && isObject$2(prevProp)) {
    return !looseEqual(nextProp, prevProp);
  }
  return nextProp !== prevProp;
}
function updateHOCHostEl(_ref14, el) {
  let vnode = _ref14.vnode,
    parent = _ref14.parent;
  while (parent) {
    const root = parent.subTree;
    if (root.suspense && root.suspense.activeBranch === vnode) {
      root.el = vnode.el;
    }
    if (root === vnode) {
      (vnode = parent.vnode).el = el;
      parent = parent.parent;
    } else {
      break;
    }
  }
}
const internalObjectProto = {};
const createInternalObject = () => Object.create(internalObjectProto);
const isInternalObject = obj => Object.getPrototypeOf(obj) === internalObjectProto;
function initProps(instance, rawProps, isStateful, isSSR) {
  if (isSSR === void 0) {
    isSSR = false;
  }
  const props = {};
  const attrs = createInternalObject();
  instance.propsDefaults = /* @__PURE__ */Object.create(null);
  setFullProps(instance, rawProps, props, attrs);
  for (const key in instance.propsOptions[0]) {
    if (!(key in props)) {
      props[key] = void 0;
    }
  }
  if (isStateful) {
    instance.props = isSSR ? props : shallowReactive(props);
  } else {
    if (!instance.type.props) {
      instance.props = attrs;
    } else {
      instance.props = props;
    }
  }
  instance.attrs = attrs;
}
function updateProps(instance, rawProps, rawPrevProps, optimized) {
  const props = instance.props,
    attrs = instance.attrs,
    patchFlag = instance.vnode.patchFlag;
  const rawCurrentProps = toRaw(props);
  const _instance$propsOption5 = instance.propsOptions,
    options = _instance$propsOption5[0];
  let hasAttrsChanged = false;
  if (
  // always force full diff in dev
  // - #1942 if hmr is enabled with sfc component
  // - vite#872 non-sfc component used by sfc component
  (optimized || patchFlag > 0) && !(patchFlag & 16)) {
    if (patchFlag & 8) {
      const propsToUpdate = instance.vnode.dynamicProps;
      for (let i = 0; i < propsToUpdate.length; i++) {
        let key = propsToUpdate[i];
        if (isEmitListener(instance.emitsOptions, key)) {
          continue;
        }
        const value = rawProps[key];
        if (options) {
          if (hasOwn(attrs, key)) {
            if (value !== attrs[key]) {
              attrs[key] = value;
              hasAttrsChanged = true;
            }
          } else {
            const camelizedKey = camelize(key);
            props[camelizedKey] = resolvePropValue(options, rawCurrentProps, camelizedKey, value, instance, false);
          }
        } else {
          if (value !== attrs[key]) {
            attrs[key] = value;
            hasAttrsChanged = true;
          }
        }
      }
    }
  } else {
    if (setFullProps(instance, rawProps, props, attrs)) {
      hasAttrsChanged = true;
    }
    let kebabKey;
    for (const key in rawCurrentProps) {
      if (!rawProps ||
      // for camelCase
      !hasOwn(rawProps, key) && (
      // it's possible the original props was passed in as kebab-case
      // and converted to camelCase (#955)
      (kebabKey = hyphenate(key)) === key || !hasOwn(rawProps, kebabKey))) {
        if (options) {
          if (rawPrevProps && (
          // for camelCase
          rawPrevProps[key] !== void 0 ||
          // for kebab-case
          rawPrevProps[kebabKey] !== void 0)) {
            props[key] = resolvePropValue(options, rawCurrentProps, key, void 0, instance, true);
          }
        } else {
          delete props[key];
        }
      }
    }
    if (attrs !== rawCurrentProps) {
      for (const key in attrs) {
        if (!rawProps || !hasOwn(rawProps, key) && true) {
          delete attrs[key];
          hasAttrsChanged = true;
        }
      }
    }
  }
  if (hasAttrsChanged) {
    trigger(instance.attrs, "set", "");
  }
}
function setFullProps(instance, rawProps, props, attrs) {
  const _instance$propsOption6 = instance.propsOptions,
    options = _instance$propsOption6[0],
    needCastKeys = _instance$propsOption6[1];
  let hasAttrsChanged = false;
  let rawCastValues;
  if (rawProps) {
    for (let key in rawProps) {
      if (isReservedProp(key)) {
        continue;
      }
      const value = rawProps[key];
      let camelKey;
      if (options && hasOwn(options, camelKey = camelize(key))) {
        if (!needCastKeys || !needCastKeys.includes(camelKey)) {
          props[camelKey] = value;
        } else {
          (rawCastValues || (rawCastValues = {}))[camelKey] = value;
        }
      } else if (!isEmitListener(instance.emitsOptions, key)) {
        if (!(key in attrs) || value !== attrs[key]) {
          attrs[key] = value;
          hasAttrsChanged = true;
        }
      }
    }
  }
  if (needCastKeys) {
    const rawCurrentProps = toRaw(props);
    const castValues = rawCastValues || EMPTY_OBJ;
    for (let i = 0; i < needCastKeys.length; i++) {
      const key = needCastKeys[i];
      props[key] = resolvePropValue(options, rawCurrentProps, key, castValues[key], instance, !hasOwn(castValues, key));
    }
  }
  return hasAttrsChanged;
}
function resolvePropValue(options, props, key, value, instance, isAbsent) {
  const opt = options[key];
  if (opt != null) {
    const hasDefault = hasOwn(opt, "default");
    if (hasDefault && value === void 0) {
      const defaultValue = opt.default;
      if (opt.type !== Function && !opt.skipFactory && isFunction(defaultValue)) {
        const propsDefaults = instance.propsDefaults;
        if (key in propsDefaults) {
          value = propsDefaults[key];
        } else {
          const reset = setCurrentInstance(instance);
          value = propsDefaults[key] = defaultValue.call(null, props);
          reset();
        }
      } else {
        value = defaultValue;
      }
      if (instance.ce) {
        instance.ce._setProp(key, value);
      }
    }
    if (opt[0 /* shouldCast */]) {
      if (isAbsent && !hasDefault) {
        value = false;
      } else if (opt[1 /* shouldCastTrue */] && (value === "" || value === hyphenate(key))) {
        value = true;
      }
    }
  }
  return value;
}
const mixinPropsCache = /* @__PURE__ */new WeakMap();
function normalizePropsOptions(comp, appContext, asMixin) {
  if (asMixin === void 0) {
    asMixin = false;
  }
  const cache = asMixin ? mixinPropsCache : appContext.propsCache;
  const cached = cache.get(comp);
  if (cached) {
    return cached;
  }
  const raw = comp.props;
  const normalized = {};
  const needCastKeys = [];
  let hasExtends = false;
  if (!isFunction(comp)) {
    const extendProps = raw2 => {
      hasExtends = true;
      const _normalizePropsOption = normalizePropsOptions(raw2, appContext, true),
        props = _normalizePropsOption[0],
        keys = _normalizePropsOption[1];
      extend$2(normalized, props);
      if (keys) needCastKeys.push(...keys);
    };
    if (!asMixin && appContext.mixins.length) {
      appContext.mixins.forEach(extendProps);
    }
    if (comp.extends) {
      extendProps(comp.extends);
    }
    if (comp.mixins) {
      comp.mixins.forEach(extendProps);
    }
  }
  if (!raw && !hasExtends) {
    if (isObject$2(comp)) {
      cache.set(comp, EMPTY_ARR);
    }
    return EMPTY_ARR;
  }
  if (isArray(raw)) {
    for (let i = 0; i < raw.length; i++) {
      const normalizedKey = camelize(raw[i]);
      if (validatePropName(normalizedKey)) {
        normalized[normalizedKey] = EMPTY_OBJ;
      }
    }
  } else if (raw) {
    for (const key in raw) {
      const normalizedKey = camelize(key);
      if (validatePropName(normalizedKey)) {
        const opt = raw[key];
        const prop = normalized[normalizedKey] = isArray(opt) || isFunction(opt) ? {
          type: opt
        } : extend$2({}, opt);
        const propType = prop.type;
        let shouldCast = false;
        let shouldCastTrue = true;
        if (isArray(propType)) {
          for (let index = 0; index < propType.length; ++index) {
            const type = propType[index];
            const typeName = isFunction(type) && type.name;
            if (typeName === "Boolean") {
              shouldCast = true;
              break;
            } else if (typeName === "String") {
              shouldCastTrue = false;
            }
          }
        } else {
          shouldCast = isFunction(propType) && propType.name === "Boolean";
        }
        prop[0 /* shouldCast */] = shouldCast;
        prop[1 /* shouldCastTrue */] = shouldCastTrue;
        if (shouldCast || hasOwn(prop, "default")) {
          needCastKeys.push(normalizedKey);
        }
      }
    }
  }
  const res = [normalized, needCastKeys];
  if (isObject$2(comp)) {
    cache.set(comp, res);
  }
  return res;
}
function validatePropName(key) {
  if (key[0] !== "$" && !isReservedProp(key)) {
    return true;
  }
  return false;
}
const isInternalKey = key => key === "_" || key === "_ctx" || key === "$stable";
const normalizeSlotValue = value => isArray(value) ? value.map(normalizeVNode) : [normalizeVNode(value)];
const normalizeSlot = (key, rawSlot, ctx) => {
  if (rawSlot._n) {
    return rawSlot;
  }
  const normalized = withCtx(function () {
    if (!!("production" !== "production") && currentInstance && !(ctx === null && currentRenderingInstance) && !(ctx && ctx.root !== currentInstance.root)) ;
    return normalizeSlotValue(rawSlot(...arguments));
  }, ctx);
  normalized._c = false;
  return normalized;
};
const normalizeObjectSlots = (rawSlots, slots, instance) => {
  const ctx = rawSlots._ctx;
  for (const key in rawSlots) {
    if (isInternalKey(key)) continue;
    const value = rawSlots[key];
    if (isFunction(value)) {
      slots[key] = normalizeSlot(key, value, ctx);
    } else if (value != null) {
      const normalized = normalizeSlotValue(value);
      slots[key] = () => normalized;
    }
  }
};
const normalizeVNodeSlots = (instance, children) => {
  const normalized = normalizeSlotValue(children);
  instance.slots.default = () => normalized;
};
const assignSlots = (slots, children, optimized) => {
  for (const key in children) {
    if (optimized || !isInternalKey(key)) {
      slots[key] = children[key];
    }
  }
};
const initSlots = (instance, children, optimized) => {
  const slots = instance.slots = createInternalObject();
  if (instance.vnode.shapeFlag & 32) {
    const type = children._;
    if (type) {
      assignSlots(slots, children, optimized);
      if (optimized) {
        def(slots, "_", type, true);
      }
    } else {
      normalizeObjectSlots(children, slots);
    }
  } else if (children) {
    normalizeVNodeSlots(instance, children);
  }
};
const updateSlots = (instance, children, optimized) => {
  const vnode = instance.vnode,
    slots = instance.slots;
  let needDeletionCheck = true;
  let deletionComparisonTarget = EMPTY_OBJ;
  if (vnode.shapeFlag & 32) {
    const type = children._;
    if (type) {
      if (optimized && type === 1) {
        needDeletionCheck = false;
      } else {
        assignSlots(slots, children, optimized);
      }
    } else {
      needDeletionCheck = !children.$stable;
      normalizeObjectSlots(children, slots);
    }
    deletionComparisonTarget = children;
  } else if (children) {
    normalizeVNodeSlots(instance, children);
    deletionComparisonTarget = {
      default: 1
    };
  }
  if (needDeletionCheck) {
    for (const key in slots) {
      if (!isInternalKey(key) && deletionComparisonTarget[key] == null) {
        delete slots[key];
      }
    }
  }
};
function initFeatureFlags() {
  if (typeof __VUE_PROD_HYDRATION_MISMATCH_DETAILS__ !== "boolean") {
    getGlobalThis().__VUE_PROD_HYDRATION_MISMATCH_DETAILS__ = false;
  }
}
const queuePostRenderEffect = queueEffectWithSuspense;
function createRenderer(options) {
  return baseCreateRenderer(options);
}
function baseCreateRenderer(options, createHydrationFns) {
  {
    initFeatureFlags();
  }
  const target = getGlobalThis();
  target.__VUE__ = true;
  const hostInsert = options.insert,
    hostRemove = options.remove,
    hostPatchProp = options.patchProp,
    hostCreateElement = options.createElement,
    hostCreateText = options.createText,
    hostCreateComment = options.createComment,
    hostSetText = options.setText,
    hostSetElementText = options.setElementText,
    hostParentNode = options.parentNode,
    hostNextSibling = options.nextSibling,
    _options$setScopeId = options.setScopeId,
    hostSetScopeId = _options$setScopeId === void 0 ? NOOP : _options$setScopeId,
    hostInsertStaticContent = options.insertStaticContent;
  const patch = function patch(n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) {
    if (anchor === void 0) {
      anchor = null;
    }
    if (parentComponent === void 0) {
      parentComponent = null;
    }
    if (parentSuspense === void 0) {
      parentSuspense = null;
    }
    if (namespace === void 0) {
      namespace = void 0;
    }
    if (slotScopeIds === void 0) {
      slotScopeIds = null;
    }
    if (optimized === void 0) {
      optimized = !!n2.dynamicChildren;
    }
    if (n1 === n2) {
      return;
    }
    if (n1 && !isSameVNodeType(n1, n2)) {
      anchor = getNextHostNode(n1);
      unmount(n1, parentComponent, parentSuspense, true);
      n1 = null;
    }
    if (n2.patchFlag === -2) {
      optimized = false;
      n2.dynamicChildren = null;
    }
    const type = n2.type,
      ref = n2.ref,
      shapeFlag = n2.shapeFlag;
    switch (type) {
      case Text:
        processText(n1, n2, container, anchor);
        break;
      case Comment:
        processCommentNode(n1, n2, container, anchor);
        break;
      case Static:
        if (n1 == null) {
          mountStaticNode(n2, container, anchor, namespace);
        }
        break;
      case Fragment:
        processFragment(n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
        break;
      default:
        if (shapeFlag & 1) {
          processElement(n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
        } else if (shapeFlag & 6) {
          processComponent(n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
        } else if (shapeFlag & 64) {
          type.process(n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized, internals);
        } else if (shapeFlag & 128) {
          type.process(n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized, internals);
        } else ;
    }
    if (ref != null && parentComponent) {
      setRef(ref, n1 && n1.ref, parentSuspense, n2 || n1, !n2);
    } else if (ref == null && n1 && n1.ref != null) {
      setRef(n1.ref, null, parentSuspense, n1, true);
    }
  };
  const processText = (n1, n2, container, anchor) => {
    if (n1 == null) {
      hostInsert(n2.el = hostCreateText(n2.children), container, anchor);
    } else {
      const el = n2.el = n1.el;
      if (n2.children !== n1.children) {
        hostSetText(el, n2.children);
      }
    }
  };
  const processCommentNode = (n1, n2, container, anchor) => {
    if (n1 == null) {
      hostInsert(n2.el = hostCreateComment(n2.children || ""), container, anchor);
    } else {
      n2.el = n1.el;
    }
  };
  const mountStaticNode = (n2, container, anchor, namespace) => {
    var _hostInsertStaticCont = hostInsertStaticContent(n2.children, container, anchor, namespace, n2.el, n2.anchor);
    n2.el = _hostInsertStaticCont[0];
    n2.anchor = _hostInsertStaticCont[1];
  };
  const moveStaticNode = (_ref15, container, nextSibling) => {
    let el = _ref15.el,
      anchor = _ref15.anchor;
    let next;
    while (el && el !== anchor) {
      next = hostNextSibling(el);
      hostInsert(el, container, nextSibling);
      el = next;
    }
    hostInsert(anchor, container, nextSibling);
  };
  const removeStaticNode = _ref16 => {
    let el = _ref16.el,
      anchor = _ref16.anchor;
    let next;
    while (el && el !== anchor) {
      next = hostNextSibling(el);
      hostRemove(el);
      el = next;
    }
    hostRemove(anchor);
  };
  const processElement = (n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    if (n2.type === "svg") {
      namespace = "svg";
    } else if (n2.type === "math") {
      namespace = "mathml";
    }
    if (n1 == null) {
      mountElement(n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
    } else {
      const customElement = n1.el && n1.el._isVueCE ? n1.el : null;
      try {
        if (customElement) {
          customElement._beginPatch();
        }
        patchElement(n1, n2, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
      } finally {
        if (customElement) {
          customElement._endPatch();
        }
      }
    }
  };
  const mountElement = (vnode, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    let el;
    let vnodeHook;
    const props = vnode.props,
      shapeFlag = vnode.shapeFlag,
      transition = vnode.transition,
      dirs = vnode.dirs;
    el = vnode.el = hostCreateElement(vnode.type, namespace, props && props.is, props);
    if (shapeFlag & 8) {
      hostSetElementText(el, vnode.children);
    } else if (shapeFlag & 16) {
      mountChildren(vnode.children, el, null, parentComponent, parentSuspense, resolveChildrenNamespace(vnode, namespace), slotScopeIds, optimized);
    }
    if (dirs) {
      invokeDirectiveHook(vnode, null, parentComponent, "created");
    }
    setScopeId(el, vnode, vnode.scopeId, slotScopeIds, parentComponent);
    if (props) {
      for (const key in props) {
        if (key !== "value" && !isReservedProp(key)) {
          hostPatchProp(el, key, null, props[key], namespace, parentComponent);
        }
      }
      if ("value" in props) {
        hostPatchProp(el, "value", null, props.value, namespace);
      }
      if (vnodeHook = props.onVnodeBeforeMount) {
        invokeVNodeHook(vnodeHook, parentComponent, vnode);
      }
    }
    if (dirs) {
      invokeDirectiveHook(vnode, null, parentComponent, "beforeMount");
    }
    const needCallTransitionHooks = needTransition(parentSuspense, transition);
    if (needCallTransitionHooks) {
      transition.beforeEnter(el);
    }
    hostInsert(el, container, anchor);
    if ((vnodeHook = props && props.onVnodeMounted) || needCallTransitionHooks || dirs) {
      queuePostRenderEffect(() => {
        vnodeHook && invokeVNodeHook(vnodeHook, parentComponent, vnode);
        needCallTransitionHooks && transition.enter(el);
        dirs && invokeDirectiveHook(vnode, null, parentComponent, "mounted");
      }, parentSuspense);
    }
  };
  const setScopeId = (el, vnode, scopeId, slotScopeIds, parentComponent) => {
    if (scopeId) {
      hostSetScopeId(el, scopeId);
    }
    if (slotScopeIds) {
      for (let i = 0; i < slotScopeIds.length; i++) {
        hostSetScopeId(el, slotScopeIds[i]);
      }
    }
    if (parentComponent) {
      let subTree = parentComponent.subTree;
      if (vnode === subTree || isSuspense(subTree.type) && (subTree.ssContent === vnode || subTree.ssFallback === vnode)) {
        const parentVNode = parentComponent.vnode;
        setScopeId(el, parentVNode, parentVNode.scopeId, parentVNode.slotScopeIds, parentComponent.parent);
      }
    }
  };
  const mountChildren = function mountChildren(children, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized, start) {
    if (start === void 0) {
      start = 0;
    }
    for (let i = start; i < children.length; i++) {
      const child = children[i] = optimized ? cloneIfMounted(children[i]) : normalizeVNode(children[i]);
      patch(null, child, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
    }
  };
  const patchElement = (n1, n2, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    const el = n2.el = n1.el;
    let patchFlag = n2.patchFlag,
      dynamicChildren = n2.dynamicChildren,
      dirs = n2.dirs;
    patchFlag |= n1.patchFlag & 16;
    const oldProps = n1.props || EMPTY_OBJ;
    const newProps = n2.props || EMPTY_OBJ;
    let vnodeHook;
    parentComponent && toggleRecurse(parentComponent, false);
    if (vnodeHook = newProps.onVnodeBeforeUpdate) {
      invokeVNodeHook(vnodeHook, parentComponent, n2, n1);
    }
    if (dirs) {
      invokeDirectiveHook(n2, n1, parentComponent, "beforeUpdate");
    }
    parentComponent && toggleRecurse(parentComponent, true);
    if (oldProps.innerHTML && newProps.innerHTML == null || oldProps.textContent && newProps.textContent == null) {
      hostSetElementText(el, "");
    }
    if (dynamicChildren) {
      patchBlockChildren(n1.dynamicChildren, dynamicChildren, el, parentComponent, parentSuspense, resolveChildrenNamespace(n2, namespace), slotScopeIds);
    } else if (!optimized) {
      patchChildren(n1, n2, el, null, parentComponent, parentSuspense, resolveChildrenNamespace(n2, namespace), slotScopeIds, false);
    }
    if (patchFlag > 0) {
      if (patchFlag & 16) {
        patchProps(el, oldProps, newProps, parentComponent, namespace);
      } else {
        if (patchFlag & 2) {
          if (oldProps.class !== newProps.class) {
            hostPatchProp(el, "class", null, newProps.class, namespace);
          }
        }
        if (patchFlag & 4) {
          hostPatchProp(el, "style", oldProps.style, newProps.style, namespace);
        }
        if (patchFlag & 8) {
          const propsToUpdate = n2.dynamicProps;
          for (let i = 0; i < propsToUpdate.length; i++) {
            const key = propsToUpdate[i];
            const prev = oldProps[key];
            const next = newProps[key];
            if (next !== prev || key === "value") {
              hostPatchProp(el, key, prev, next, namespace, parentComponent);
            }
          }
        }
      }
      if (patchFlag & 1) {
        if (n1.children !== n2.children) {
          hostSetElementText(el, n2.children);
        }
      }
    } else if (!optimized && dynamicChildren == null) {
      patchProps(el, oldProps, newProps, parentComponent, namespace);
    }
    if ((vnodeHook = newProps.onVnodeUpdated) || dirs) {
      queuePostRenderEffect(() => {
        vnodeHook && invokeVNodeHook(vnodeHook, parentComponent, n2, n1);
        dirs && invokeDirectiveHook(n2, n1, parentComponent, "updated");
      }, parentSuspense);
    }
  };
  const patchBlockChildren = (oldChildren, newChildren, fallbackContainer, parentComponent, parentSuspense, namespace, slotScopeIds) => {
    for (let i = 0; i < newChildren.length; i++) {
      const oldVNode = oldChildren[i];
      const newVNode = newChildren[i];
      const container =
      // oldVNode may be an errored async setup() component inside Suspense
      // which will not have a mounted element
      oldVNode.el && (
      // - In the case of a Fragment, we need to provide the actual parent
      // of the Fragment itself so it can move its children.
      oldVNode.type === Fragment ||
      // - In the case of different nodes, there is going to be a replacement
      // which also requires the correct parent container
      !isSameVNodeType(oldVNode, newVNode) ||
      // - In the case of a component, it could contain anything.
      oldVNode.shapeFlag & (6 | 64 | 128)) ? hostParentNode(oldVNode.el) :
      // In other cases, the parent container is not actually used so we
      // just pass the block element here to avoid a DOM parentNode call.
      fallbackContainer;
      patch(oldVNode, newVNode, container, null, parentComponent, parentSuspense, namespace, slotScopeIds, true);
    }
  };
  const patchProps = (el, oldProps, newProps, parentComponent, namespace) => {
    if (oldProps !== newProps) {
      if (oldProps !== EMPTY_OBJ) {
        for (const key in oldProps) {
          if (!isReservedProp(key) && !(key in newProps)) {
            hostPatchProp(el, key, oldProps[key], null, namespace, parentComponent);
          }
        }
      }
      for (const key in newProps) {
        if (isReservedProp(key)) continue;
        const next = newProps[key];
        const prev = oldProps[key];
        if (next !== prev && key !== "value") {
          hostPatchProp(el, key, prev, next, namespace, parentComponent);
        }
      }
      if ("value" in newProps) {
        hostPatchProp(el, "value", oldProps.value, newProps.value, namespace);
      }
    }
  };
  const processFragment = (n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    const fragmentStartAnchor = n2.el = n1 ? n1.el : hostCreateText("");
    const fragmentEndAnchor = n2.anchor = n1 ? n1.anchor : hostCreateText("");
    let patchFlag = n2.patchFlag,
      dynamicChildren = n2.dynamicChildren,
      fragmentSlotScopeIds = n2.slotScopeIds;
    if (fragmentSlotScopeIds) {
      slotScopeIds = slotScopeIds ? slotScopeIds.concat(fragmentSlotScopeIds) : fragmentSlotScopeIds;
    }
    if (n1 == null) {
      hostInsert(fragmentStartAnchor, container, anchor);
      hostInsert(fragmentEndAnchor, container, anchor);
      mountChildren(
      // #10007
      // such fragment like `<></>` will be compiled into
      // a fragment which doesn't have a children.
      // In this case fallback to an empty array
      n2.children || [], container, fragmentEndAnchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
    } else {
      if (patchFlag > 0 && patchFlag & 64 && dynamicChildren &&
      // #2715 the previous fragment could've been a BAILed one as a result
      // of renderSlot() with no valid children
      n1.dynamicChildren && n1.dynamicChildren.length === dynamicChildren.length) {
        patchBlockChildren(n1.dynamicChildren, dynamicChildren, container, parentComponent, parentSuspense, namespace, slotScopeIds);
        if (
        // #2080 if the stable fragment has a key, it's a <template v-for> that may
        //  get moved around. Make sure all root level vnodes inherit el.
        // #2134 or if it's a component root, it may also get moved around
        // as the component is being moved.
        n2.key != null || parentComponent && n2 === parentComponent.subTree) {
          traverseStaticChildren(n1, n2);
        }
      } else {
        patchChildren(n1, n2, container, fragmentEndAnchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
      }
    }
  };
  const processComponent = (n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    n2.slotScopeIds = slotScopeIds;
    if (n1 == null) {
      if (n2.shapeFlag & 512) {
        parentComponent.ctx.activate(n2, container, anchor, namespace, optimized);
      } else {
        mountComponent(n2, container, anchor, parentComponent, parentSuspense, namespace, optimized);
      }
    } else {
      updateComponent(n1, n2, optimized);
    }
  };
  const mountComponent = (initialVNode, container, anchor, parentComponent, parentSuspense, namespace, optimized) => {
    const instance = initialVNode.component = createComponentInstance(initialVNode, parentComponent, parentSuspense);
    if (isKeepAlive(initialVNode)) {
      instance.ctx.renderer = internals;
    }
    {
      setupComponent(instance, false, optimized);
    }
    if (instance.asyncDep) {
      parentSuspense && parentSuspense.registerDep(instance, setupRenderEffect, optimized);
      if (!initialVNode.el) {
        const placeholder = instance.subTree = createVNode(Comment);
        processCommentNode(null, placeholder, container, anchor);
        initialVNode.placeholder = placeholder.el;
      }
    } else {
      setupRenderEffect(instance, initialVNode, container, anchor, parentSuspense, namespace, optimized);
    }
  };
  const updateComponent = (n1, n2, optimized) => {
    const instance = n2.component = n1.component;
    if (shouldUpdateComponent(n1, n2, optimized)) {
      if (instance.asyncDep && !instance.asyncResolved) {
        updateComponentPreRender(instance, n2, optimized);
        return;
      } else {
        instance.next = n2;
        instance.update();
      }
    } else {
      n2.el = n1.el;
      instance.vnode = n2;
    }
  };
  const setupRenderEffect = (instance, initialVNode, container, anchor, parentSuspense, namespace, optimized) => {
    const componentUpdateFn = () => {
      if (!instance.isMounted) {
        let vnodeHook;
        const _initialVNode = initialVNode;
          _initialVNode.el;
          const props = _initialVNode.props;
        const bm = instance.bm,
          m = instance.m,
          parent = instance.parent,
          root = instance.root,
          type = instance.type;
        const isAsyncWrapperVNode = isAsyncWrapper(initialVNode);
        toggleRecurse(instance, false);
        if (bm) {
          invokeArrayFns(bm);
        }
        if (!isAsyncWrapperVNode && (vnodeHook = props && props.onVnodeBeforeMount)) {
          invokeVNodeHook(vnodeHook, parent, initialVNode);
        }
        toggleRecurse(instance, true);
        {
          if (root.ce && root.ce._hasShadowRoot()) {
            root.ce._injectChildStyle(type, instance.parent ? instance.parent.type : void 0);
          }
          const subTree = instance.subTree = renderComponentRoot(instance);
          patch(null, subTree, container, anchor, instance, parentSuspense, namespace);
          initialVNode.el = subTree.el;
        }
        if (m) {
          queuePostRenderEffect(m, parentSuspense);
        }
        if (!isAsyncWrapperVNode && (vnodeHook = props && props.onVnodeMounted)) {
          const scopedInitialVNode = initialVNode;
          queuePostRenderEffect(() => invokeVNodeHook(vnodeHook, parent, scopedInitialVNode), parentSuspense);
        }
        if (initialVNode.shapeFlag & 256 || parent && isAsyncWrapper(parent.vnode) && parent.vnode.shapeFlag & 256) {
          instance.a && queuePostRenderEffect(instance.a, parentSuspense);
        }
        instance.isMounted = true;
        initialVNode = container = anchor = null;
      } else {
        let next = instance.next,
          bu = instance.bu,
          u = instance.u,
          parent = instance.parent,
          vnode = instance.vnode;
        {
          const nonHydratedAsyncRoot = locateNonHydratedAsyncRoot(instance);
          if (nonHydratedAsyncRoot) {
            if (next) {
              next.el = vnode.el;
              updateComponentPreRender(instance, next, optimized);
            }
            nonHydratedAsyncRoot.asyncDep.then(() => {
              queuePostRenderEffect(() => {
                if (!instance.isUnmounted) update();
              }, parentSuspense);
            });
            return;
          }
        }
        let originNext = next;
        let vnodeHook;
        toggleRecurse(instance, false);
        if (next) {
          next.el = vnode.el;
          updateComponentPreRender(instance, next, optimized);
        } else {
          next = vnode;
        }
        if (bu) {
          invokeArrayFns(bu);
        }
        if (vnodeHook = next.props && next.props.onVnodeBeforeUpdate) {
          invokeVNodeHook(vnodeHook, parent, next, vnode);
        }
        toggleRecurse(instance, true);
        const nextTree = renderComponentRoot(instance);
        const prevTree = instance.subTree;
        instance.subTree = nextTree;
        patch(prevTree, nextTree,
        // parent may have changed if it's in a teleport
        hostParentNode(prevTree.el),
        // anchor may have changed if it's in a fragment
        getNextHostNode(prevTree), instance, parentSuspense, namespace);
        next.el = nextTree.el;
        if (originNext === null) {
          updateHOCHostEl(instance, nextTree.el);
        }
        if (u) {
          queuePostRenderEffect(u, parentSuspense);
        }
        if (vnodeHook = next.props && next.props.onVnodeUpdated) {
          queuePostRenderEffect(() => invokeVNodeHook(vnodeHook, parent, next, vnode), parentSuspense);
        }
      }
    };
    instance.scope.on();
    const effect = instance.effect = new ReactiveEffect(componentUpdateFn);
    instance.scope.off();
    const update = instance.update = effect.run.bind(effect);
    const job = instance.job = effect.runIfDirty.bind(effect);
    job.i = instance;
    job.id = instance.uid;
    effect.scheduler = () => queueJob(job);
    toggleRecurse(instance, true);
    update();
  };
  const updateComponentPreRender = (instance, nextVNode, optimized) => {
    nextVNode.component = instance;
    const prevProps = instance.vnode.props;
    instance.vnode = nextVNode;
    instance.next = null;
    updateProps(instance, nextVNode.props, prevProps, optimized);
    updateSlots(instance, nextVNode.children, optimized);
    pauseTracking();
    flushPreFlushCbs(instance);
    resetTracking();
  };
  const patchChildren = function patchChildren(n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) {
    if (optimized === void 0) {
      optimized = false;
    }
    const c1 = n1 && n1.children;
    const prevShapeFlag = n1 ? n1.shapeFlag : 0;
    const c2 = n2.children;
    const patchFlag = n2.patchFlag,
      shapeFlag = n2.shapeFlag;
    if (patchFlag > 0) {
      if (patchFlag & 128) {
        patchKeyedChildren(c1, c2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
        return;
      } else if (patchFlag & 256) {
        patchUnkeyedChildren(c1, c2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
        return;
      }
    }
    if (shapeFlag & 8) {
      if (prevShapeFlag & 16) {
        unmountChildren(c1, parentComponent, parentSuspense);
      }
      if (c2 !== c1) {
        hostSetElementText(container, c2);
      }
    } else {
      if (prevShapeFlag & 16) {
        if (shapeFlag & 16) {
          patchKeyedChildren(c1, c2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
        } else {
          unmountChildren(c1, parentComponent, parentSuspense, true);
        }
      } else {
        if (prevShapeFlag & 8) {
          hostSetElementText(container, "");
        }
        if (shapeFlag & 16) {
          mountChildren(c2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
        }
      }
    }
  };
  const patchUnkeyedChildren = (c1, c2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    c1 = c1 || EMPTY_ARR;
    c2 = c2 || EMPTY_ARR;
    const oldLength = c1.length;
    const newLength = c2.length;
    const commonLength = Math.min(oldLength, newLength);
    let i;
    for (i = 0; i < commonLength; i++) {
      const nextChild = c2[i] = optimized ? cloneIfMounted(c2[i]) : normalizeVNode(c2[i]);
      patch(c1[i], nextChild, container, null, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
    }
    if (oldLength > newLength) {
      unmountChildren(c1, parentComponent, parentSuspense, true, false, commonLength);
    } else {
      mountChildren(c2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized, commonLength);
    }
  };
  const patchKeyedChildren = (c1, c2, container, parentAnchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    let i = 0;
    const l2 = c2.length;
    let e1 = c1.length - 1;
    let e2 = l2 - 1;
    while (i <= e1 && i <= e2) {
      const n1 = c1[i];
      const n2 = c2[i] = optimized ? cloneIfMounted(c2[i]) : normalizeVNode(c2[i]);
      if (isSameVNodeType(n1, n2)) {
        patch(n1, n2, container, null, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
      } else {
        break;
      }
      i++;
    }
    while (i <= e1 && i <= e2) {
      const n1 = c1[e1];
      const n2 = c2[e2] = optimized ? cloneIfMounted(c2[e2]) : normalizeVNode(c2[e2]);
      if (isSameVNodeType(n1, n2)) {
        patch(n1, n2, container, null, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
      } else {
        break;
      }
      e1--;
      e2--;
    }
    if (i > e1) {
      if (i <= e2) {
        const nextPos = e2 + 1;
        const anchor = nextPos < l2 ? c2[nextPos].el : parentAnchor;
        while (i <= e2) {
          patch(null, c2[i] = optimized ? cloneIfMounted(c2[i]) : normalizeVNode(c2[i]), container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
          i++;
        }
      }
    } else if (i > e2) {
      while (i <= e1) {
        unmount(c1[i], parentComponent, parentSuspense, true);
        i++;
      }
    } else {
      const s1 = i;
      const s2 = i;
      const keyToNewIndexMap = /* @__PURE__ */new Map();
      for (i = s2; i <= e2; i++) {
        const nextChild = c2[i] = optimized ? cloneIfMounted(c2[i]) : normalizeVNode(c2[i]);
        if (nextChild.key != null) {
          keyToNewIndexMap.set(nextChild.key, i);
        }
      }
      let j;
      let patched = 0;
      const toBePatched = e2 - s2 + 1;
      let moved = false;
      let maxNewIndexSoFar = 0;
      const newIndexToOldIndexMap = new Array(toBePatched);
      for (i = 0; i < toBePatched; i++) newIndexToOldIndexMap[i] = 0;
      for (i = s1; i <= e1; i++) {
        const prevChild = c1[i];
        if (patched >= toBePatched) {
          unmount(prevChild, parentComponent, parentSuspense, true);
          continue;
        }
        let newIndex;
        if (prevChild.key != null) {
          newIndex = keyToNewIndexMap.get(prevChild.key);
        } else {
          for (j = s2; j <= e2; j++) {
            if (newIndexToOldIndexMap[j - s2] === 0 && isSameVNodeType(prevChild, c2[j])) {
              newIndex = j;
              break;
            }
          }
        }
        if (newIndex === void 0) {
          unmount(prevChild, parentComponent, parentSuspense, true);
        } else {
          newIndexToOldIndexMap[newIndex - s2] = i + 1;
          if (newIndex >= maxNewIndexSoFar) {
            maxNewIndexSoFar = newIndex;
          } else {
            moved = true;
          }
          patch(prevChild, c2[newIndex], container, null, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
          patched++;
        }
      }
      const increasingNewIndexSequence = moved ? getSequence(newIndexToOldIndexMap) : EMPTY_ARR;
      j = increasingNewIndexSequence.length - 1;
      for (i = toBePatched - 1; i >= 0; i--) {
        const nextIndex = s2 + i;
        const nextChild = c2[nextIndex];
        const anchorVNode = c2[nextIndex + 1];
        const anchor = nextIndex + 1 < l2 ?
        // #13559, #14173 fallback to el placeholder for unresolved async component
        anchorVNode.el || resolveAsyncComponentPlaceholder(anchorVNode) : parentAnchor;
        if (newIndexToOldIndexMap[i] === 0) {
          patch(null, nextChild, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized);
        } else if (moved) {
          if (j < 0 || i !== increasingNewIndexSequence[j]) {
            _move(nextChild, container, anchor, 2);
          } else {
            j--;
          }
        }
      }
    }
  };
  const _move = function move(vnode, container, anchor, moveType, parentSuspense) {
    if (parentSuspense === void 0) {
      parentSuspense = null;
    }
    const el = vnode.el,
      type = vnode.type,
      transition = vnode.transition,
      children = vnode.children,
      shapeFlag = vnode.shapeFlag;
    if (shapeFlag & 6) {
      _move(vnode.component.subTree, container, anchor, moveType);
      return;
    }
    if (shapeFlag & 128) {
      vnode.suspense.move(container, anchor, moveType);
      return;
    }
    if (shapeFlag & 64) {
      type.move(vnode, container, anchor, internals);
      return;
    }
    if (type === Fragment) {
      hostInsert(el, container, anchor);
      for (let i = 0; i < children.length; i++) {
        _move(children[i], container, anchor, moveType);
      }
      hostInsert(vnode.anchor, container, anchor);
      return;
    }
    if (type === Static) {
      moveStaticNode(vnode, container, anchor);
      return;
    }
    const needTransition2 = moveType !== 2 && shapeFlag & 1 && transition;
    if (needTransition2) {
      if (moveType === 0) {
        transition.beforeEnter(el);
        hostInsert(el, container, anchor);
        queuePostRenderEffect(() => transition.enter(el), parentSuspense);
      } else {
        const leave = transition.leave,
          delayLeave = transition.delayLeave,
          afterLeave = transition.afterLeave;
        const remove2 = () => {
          if (vnode.ctx.isUnmounted) {
            hostRemove(el);
          } else {
            hostInsert(el, container, anchor);
          }
        };
        const performLeave = () => {
          if (el._isLeaving) {
            el[leaveCbKey](true
            /* cancelled */);
          }
          leave(el, () => {
            remove2();
            afterLeave && afterLeave();
          });
        };
        if (delayLeave) {
          delayLeave(el, remove2, performLeave);
        } else {
          performLeave();
        }
      }
    } else {
      hostInsert(el, container, anchor);
    }
  };
  const unmount = function unmount(vnode, parentComponent, parentSuspense, doRemove, optimized) {
    if (doRemove === void 0) {
      doRemove = false;
    }
    if (optimized === void 0) {
      optimized = false;
    }
    const type = vnode.type,
      props = vnode.props,
      ref = vnode.ref,
      children = vnode.children,
      dynamicChildren = vnode.dynamicChildren,
      shapeFlag = vnode.shapeFlag,
      patchFlag = vnode.patchFlag,
      dirs = vnode.dirs,
      cacheIndex = vnode.cacheIndex;
    if (patchFlag === -2) {
      optimized = false;
    }
    if (ref != null) {
      pauseTracking();
      setRef(ref, null, parentSuspense, vnode, true);
      resetTracking();
    }
    if (cacheIndex != null) {
      parentComponent.renderCache[cacheIndex] = void 0;
    }
    if (shapeFlag & 256) {
      parentComponent.ctx.deactivate(vnode);
      return;
    }
    const shouldInvokeDirs = shapeFlag & 1 && dirs;
    const shouldInvokeVnodeHook = !isAsyncWrapper(vnode);
    let vnodeHook;
    if (shouldInvokeVnodeHook && (vnodeHook = props && props.onVnodeBeforeUnmount)) {
      invokeVNodeHook(vnodeHook, parentComponent, vnode);
    }
    if (shapeFlag & 6) {
      unmountComponent(vnode.component, parentSuspense, doRemove);
    } else {
      if (shapeFlag & 128) {
        vnode.suspense.unmount(parentSuspense, doRemove);
        return;
      }
      if (shouldInvokeDirs) {
        invokeDirectiveHook(vnode, null, parentComponent, "beforeUnmount");
      }
      if (shapeFlag & 64) {
        vnode.type.remove(vnode, parentComponent, parentSuspense, internals, doRemove);
      } else if (dynamicChildren &&
      // #5154
      // when v-once is used inside a block, setBlockTracking(-1) marks the
      // parent block with hasOnce: true
      // so that it doesn't take the fast path during unmount - otherwise
      // components nested in v-once are never unmounted.
      !dynamicChildren.hasOnce && (
      // #1153: fast path should not be taken for non-stable (v-for) fragments
      type !== Fragment || patchFlag > 0 && patchFlag & 64)) {
        unmountChildren(dynamicChildren, parentComponent, parentSuspense, false, true);
      } else if (type === Fragment && patchFlag & (128 | 256) || !optimized && shapeFlag & 16) {
        unmountChildren(children, parentComponent, parentSuspense);
      }
      if (doRemove) {
        remove(vnode);
      }
    }
    if (shouldInvokeVnodeHook && (vnodeHook = props && props.onVnodeUnmounted) || shouldInvokeDirs) {
      queuePostRenderEffect(() => {
        vnodeHook && invokeVNodeHook(vnodeHook, parentComponent, vnode);
        shouldInvokeDirs && invokeDirectiveHook(vnode, null, parentComponent, "unmounted");
      }, parentSuspense);
    }
  };
  const remove = vnode => {
    const type = vnode.type,
      el = vnode.el,
      anchor = vnode.anchor,
      transition = vnode.transition;
    if (type === Fragment) {
      {
        removeFragment(el, anchor);
      }
      return;
    }
    if (type === Static) {
      removeStaticNode(vnode);
      return;
    }
    const performRemove = () => {
      hostRemove(el);
      if (transition && !transition.persisted && transition.afterLeave) {
        transition.afterLeave();
      }
    };
    if (vnode.shapeFlag & 1 && transition && !transition.persisted) {
      const leave = transition.leave,
        delayLeave = transition.delayLeave;
      const performLeave = () => leave(el, performRemove);
      if (delayLeave) {
        delayLeave(vnode.el, performRemove, performLeave);
      } else {
        performLeave();
      }
    } else {
      performRemove();
    }
  };
  const removeFragment = (cur, end) => {
    let next;
    while (cur !== end) {
      next = hostNextSibling(cur);
      hostRemove(cur);
      cur = next;
    }
    hostRemove(end);
  };
  const unmountComponent = (instance, parentSuspense, doRemove) => {
    const bum = instance.bum,
      scope = instance.scope,
      job = instance.job,
      subTree = instance.subTree,
      um = instance.um,
      m = instance.m,
      a = instance.a;
    invalidateMount(m);
    invalidateMount(a);
    if (bum) {
      invokeArrayFns(bum);
    }
    scope.stop();
    if (job) {
      job.flags |= 8;
      unmount(subTree, instance, parentSuspense, doRemove);
    }
    if (um) {
      queuePostRenderEffect(um, parentSuspense);
    }
    queuePostRenderEffect(() => {
      instance.isUnmounted = true;
    }, parentSuspense);
  };
  const unmountChildren = function unmountChildren(children, parentComponent, parentSuspense, doRemove, optimized, start) {
    if (doRemove === void 0) {
      doRemove = false;
    }
    if (optimized === void 0) {
      optimized = false;
    }
    if (start === void 0) {
      start = 0;
    }
    for (let i = start; i < children.length; i++) {
      unmount(children[i], parentComponent, parentSuspense, doRemove, optimized);
    }
  };
  const getNextHostNode = vnode => {
    if (vnode.shapeFlag & 6) {
      return getNextHostNode(vnode.component.subTree);
    }
    if (vnode.shapeFlag & 128) {
      return vnode.suspense.next();
    }
    const el = hostNextSibling(vnode.anchor || vnode.el);
    const teleportEnd = el && el[TeleportEndKey];
    return teleportEnd ? hostNextSibling(teleportEnd) : el;
  };
  let isFlushing = false;
  const render = (vnode, container, namespace) => {
    let instance;
    if (vnode == null) {
      if (container._vnode) {
        unmount(container._vnode, null, null, true);
        instance = container._vnode.component;
      }
    } else {
      patch(container._vnode || null, vnode, container, null, null, null, namespace);
    }
    container._vnode = vnode;
    if (!isFlushing) {
      isFlushing = true;
      flushPreFlushCbs(instance);
      flushPostFlushCbs();
      isFlushing = false;
    }
  };
  const internals = {
    p: patch,
    um: unmount,
    m: _move,
    r: remove,
    mt: mountComponent,
    mc: mountChildren,
    pc: patchChildren,
    pbc: patchBlockChildren,
    n: getNextHostNode,
    o: options
  };
  let hydrate;
  return {
    render,
    hydrate,
    createApp: createAppAPI(render)
  };
}
function resolveChildrenNamespace(_ref17, currentNamespace) {
  let type = _ref17.type,
    props = _ref17.props;
  return currentNamespace === "svg" && type === "foreignObject" || currentNamespace === "mathml" && type === "annotation-xml" && props && props.encoding && props.encoding.includes("html") ? void 0 : currentNamespace;
}
function toggleRecurse(_ref18, allowed) {
  let effect = _ref18.effect,
    job = _ref18.job;
  if (allowed) {
    effect.flags |= 32;
    job.flags |= 4;
  } else {
    effect.flags &= -33;
    job.flags &= -5;
  }
}
function needTransition(parentSuspense, transition) {
  return (!parentSuspense || parentSuspense && !parentSuspense.pendingBranch) && transition && !transition.persisted;
}
function traverseStaticChildren(n1, n2, shallow) {
  const ch1 = n1.children;
  const ch2 = n2.children;
  if (isArray(ch1) && isArray(ch2)) {
    for (let i = 0; i < ch1.length; i++) {
      const c1 = ch1[i];
      let c2 = ch2[i];
      if (c2.shapeFlag & 1 && !c2.dynamicChildren) {
        if (c2.patchFlag <= 0 || c2.patchFlag === 32) {
          c2 = ch2[i] = cloneIfMounted(ch2[i]);
          c2.el = c1.el;
        }
      }
      if (c2.type === Text) {
        if (c2.patchFlag === -1) {
          c2 = ch2[i] = cloneIfMounted(c2);
        }
        c2.el = c1.el;
      }
      if (c2.type === Comment && !c2.el) {
        c2.el = c1.el;
      }
    }
  }
}
function getSequence(arr) {
  const p = arr.slice();
  const result = [0];
  let i, j, u, v, c;
  const len = arr.length;
  for (i = 0; i < len; i++) {
    const arrI = arr[i];
    if (arrI !== 0) {
      j = result[result.length - 1];
      if (arr[j] < arrI) {
        p[i] = j;
        result.push(i);
        continue;
      }
      u = 0;
      v = result.length - 1;
      while (u < v) {
        c = u + v >> 1;
        if (arr[result[c]] < arrI) {
          u = c + 1;
        } else {
          v = c;
        }
      }
      if (arrI < arr[result[u]]) {
        if (u > 0) {
          p[i] = result[u - 1];
        }
        result[u] = i;
      }
    }
  }
  u = result.length;
  v = result[u - 1];
  while (u-- > 0) {
    result[u] = v;
    v = p[v];
  }
  return result;
}
function locateNonHydratedAsyncRoot(instance) {
  const subComponent = instance.subTree.component;
  if (subComponent) {
    if (subComponent.asyncDep && !subComponent.asyncResolved) {
      return subComponent;
    } else {
      return locateNonHydratedAsyncRoot(subComponent);
    }
  }
}
function invalidateMount(hooks) {
  if (hooks) {
    for (let i = 0; i < hooks.length; i++) hooks[i].flags |= 8;
  }
}
function resolveAsyncComponentPlaceholder(anchorVnode) {
  if (anchorVnode.placeholder) {
    return anchorVnode.placeholder;
  }
  const instance = anchorVnode.component;
  if (instance) {
    return resolveAsyncComponentPlaceholder(instance.subTree);
  }
  return null;
}
const isSuspense = type => type.__isSuspense;
function queueEffectWithSuspense(fn, suspense) {
  if (suspense && suspense.pendingBranch) {
    if (isArray(fn)) {
      suspense.effects.push(...fn);
    } else {
      suspense.effects.push(fn);
    }
  } else {
    queuePostFlushCb(fn);
  }
}
const Fragment = /* @__PURE__ */Symbol.for("v-fgt");
const Text = /* @__PURE__ */Symbol.for("v-txt");
const Comment = /* @__PURE__ */Symbol.for("v-cmt");
const Static = /* @__PURE__ */Symbol.for("v-stc");
const blockStack = [];
let currentBlock = null;
function openBlock(disableTracking) {
  if (disableTracking === void 0) {
    disableTracking = false;
  }
  blockStack.push(currentBlock = disableTracking ? null : []);
}
function closeBlock() {
  blockStack.pop();
  currentBlock = blockStack[blockStack.length - 1] || null;
}
let isBlockTreeEnabled = 1;
function setBlockTracking(value, inVOnce) {
  if (inVOnce === void 0) {
    inVOnce = false;
  }
  isBlockTreeEnabled += value;
  if (value < 0 && currentBlock && inVOnce) {
    currentBlock.hasOnce = true;
  }
}
function setupBlock(vnode) {
  vnode.dynamicChildren = isBlockTreeEnabled > 0 ? currentBlock || EMPTY_ARR : null;
  closeBlock();
  if (isBlockTreeEnabled > 0 && currentBlock) {
    currentBlock.push(vnode);
  }
  return vnode;
}
function createElementBlock(type, props, children, patchFlag, dynamicProps, shapeFlag) {
  return setupBlock(createBaseVNode(type, props, children, patchFlag, dynamicProps, shapeFlag, true));
}
function createBlock(type, props, children, patchFlag, dynamicProps) {
  return setupBlock(createVNode(type, props, children, patchFlag, dynamicProps, true));
}
function isVNode(value) {
  return value ? value.__v_isVNode === true : false;
}
function isSameVNodeType(n1, n2) {
  return n1.type === n2.type && n1.key === n2.key;
}
const normalizeKey = _ref20 => {
  let key = _ref20.key;
  return key != null ? key : null;
};
const normalizeRef = _ref21 => {
  let ref = _ref21.ref,
    ref_key = _ref21.ref_key,
    ref_for = _ref21.ref_for;
  if (typeof ref === "number") {
    ref = "" + ref;
  }
  return ref != null ? isString(ref) || isRef(ref) || isFunction(ref) ? {
    i: currentRenderingInstance,
    r: ref,
    k: ref_key,
    f: !!ref_for
  } : ref : null;
};
function createBaseVNode(type, props, children, patchFlag, dynamicProps, shapeFlag, isBlockNode, needFullChildrenNormalization) {
  if (props === void 0) {
    props = null;
  }
  if (children === void 0) {
    children = null;
  }
  if (patchFlag === void 0) {
    patchFlag = 0;
  }
  if (dynamicProps === void 0) {
    dynamicProps = null;
  }
  if (shapeFlag === void 0) {
    shapeFlag = type === Fragment ? 0 : 1;
  }
  if (isBlockNode === void 0) {
    isBlockNode = false;
  }
  if (needFullChildrenNormalization === void 0) {
    needFullChildrenNormalization = false;
  }
  const vnode = {
    __v_isVNode: true,
    __v_skip: true,
    type,
    props,
    key: props && normalizeKey(props),
    ref: props && normalizeRef(props),
    scopeId: currentScopeId,
    slotScopeIds: null,
    children,
    component: null,
    suspense: null,
    ssContent: null,
    ssFallback: null,
    dirs: null,
    transition: null,
    el: null,
    anchor: null,
    target: null,
    targetStart: null,
    targetAnchor: null,
    staticCount: 0,
    shapeFlag,
    patchFlag,
    dynamicProps,
    dynamicChildren: null,
    appContext: null,
    ctx: currentRenderingInstance
  };
  if (needFullChildrenNormalization) {
    normalizeChildren(vnode, children);
    if (shapeFlag & 128) {
      type.normalize(vnode);
    }
  } else if (children) {
    vnode.shapeFlag |= isString(children) ? 8 : 16;
  }
  if (isBlockTreeEnabled > 0 &&
  // avoid a block node from tracking itself
  !isBlockNode &&
  // has current parent block
  currentBlock && (
  // presence of a patch flag indicates this node needs patching on updates.
  // component nodes also should always be patched, because even if the
  // component doesn't need to update, it needs to persist the instance on to
  // the next vnode so that it can be properly unmounted later.
  vnode.patchFlag > 0 || shapeFlag & 6) &&
  // the EVENTS flag is only for hydration and if it is the only flag, the
  // vnode should not be considered dynamic due to handler caching.
  vnode.patchFlag !== 32) {
    currentBlock.push(vnode);
  }
  return vnode;
}
const createVNode = _createVNode;
function _createVNode(type, props, children, patchFlag, dynamicProps, isBlockNode) {
  if (props === void 0) {
    props = null;
  }
  if (children === void 0) {
    children = null;
  }
  if (patchFlag === void 0) {
    patchFlag = 0;
  }
  if (dynamicProps === void 0) {
    dynamicProps = null;
  }
  if (isBlockNode === void 0) {
    isBlockNode = false;
  }
  if (!type || type === NULL_DYNAMIC_COMPONENT) {
    type = Comment;
  }
  if (isVNode(type)) {
    const cloned = cloneVNode(type, props, true
    /* mergeRef: true */);
    if (children) {
      normalizeChildren(cloned, children);
    }
    if (isBlockTreeEnabled > 0 && !isBlockNode && currentBlock) {
      if (cloned.shapeFlag & 6) {
        currentBlock[currentBlock.indexOf(type)] = cloned;
      } else {
        currentBlock.push(cloned);
      }
    }
    cloned.patchFlag = -2;
    return cloned;
  }
  if (isClassComponent(type)) {
    type = type.__vccOpts;
  }
  if (props) {
    props = guardReactiveProps(props);
    let _props = props,
      klass = _props.class,
      style = _props.style;
    if (klass && !isString(klass)) {
      props.class = normalizeClass(klass);
    }
    if (isObject$2(style)) {
      if (isProxy(style) && !isArray(style)) {
        style = extend$2({}, style);
      }
      props.style = normalizeStyle(style);
    }
  }
  const shapeFlag = isString(type) ? 1 : isSuspense(type) ? 128 : isTeleport(type) ? 64 : isObject$2(type) ? 4 : isFunction(type) ? 2 : 0;
  return createBaseVNode(type, props, children, patchFlag, dynamicProps, shapeFlag, isBlockNode, true);
}
function guardReactiveProps(props) {
  if (!props) return null;
  return isProxy(props) || isInternalObject(props) ? extend$2({}, props) : props;
}
function cloneVNode(vnode, extraProps, mergeRef, cloneTransition) {
  if (mergeRef === void 0) {
    mergeRef = false;
  }
  if (cloneTransition === void 0) {
    cloneTransition = false;
  }
  const props = vnode.props,
    ref = vnode.ref,
    patchFlag = vnode.patchFlag,
    children = vnode.children,
    transition = vnode.transition;
  const mergedProps = extraProps ? mergeProps(props || {}, extraProps) : props;
  const cloned = {
    __v_isVNode: true,
    __v_skip: true,
    type: vnode.type,
    props: mergedProps,
    key: mergedProps && normalizeKey(mergedProps),
    ref: extraProps && extraProps.ref ?
    // #2078 in the case of <component :is="vnode" ref="extra"/>
    // if the vnode itself already has a ref, cloneVNode will need to merge
    // the refs so the single vnode can be set on multiple refs
    mergeRef && ref ? isArray(ref) ? ref.concat(normalizeRef(extraProps)) : [ref, normalizeRef(extraProps)] : normalizeRef(extraProps) : ref,
    scopeId: vnode.scopeId,
    slotScopeIds: vnode.slotScopeIds,
    children: children,
    target: vnode.target,
    targetStart: vnode.targetStart,
    targetAnchor: vnode.targetAnchor,
    staticCount: vnode.staticCount,
    shapeFlag: vnode.shapeFlag,
    // if the vnode is cloned with extra props, we can no longer assume its
    // existing patch flag to be reliable and need to add the FULL_PROPS flag.
    // note: preserve flag for fragments since they use the flag for children
    // fast paths only.
    patchFlag: extraProps && vnode.type !== Fragment ? patchFlag === -1 ? 16 : patchFlag | 16 : patchFlag,
    dynamicProps: vnode.dynamicProps,
    dynamicChildren: vnode.dynamicChildren,
    appContext: vnode.appContext,
    dirs: vnode.dirs,
    transition,
    // These should technically only be non-null on mounted VNodes. However,
    // they *should* be copied for kept-alive vnodes. So we just always copy
    // them since them being non-null during a mount doesn't affect the logic as
    // they will simply be overwritten.
    component: vnode.component,
    suspense: vnode.suspense,
    ssContent: vnode.ssContent && cloneVNode(vnode.ssContent),
    ssFallback: vnode.ssFallback && cloneVNode(vnode.ssFallback),
    placeholder: vnode.placeholder,
    el: vnode.el,
    anchor: vnode.anchor,
    ctx: vnode.ctx,
    ce: vnode.ce
  };
  if (transition && cloneTransition) {
    setTransitionHooks(cloned, transition.clone(cloned));
  }
  return cloned;
}
function createTextVNode(text, flag) {
  if (text === void 0) {
    text = " ";
  }
  if (flag === void 0) {
    flag = 0;
  }
  return createVNode(Text, null, text, flag);
}
function createCommentVNode(text, asBlock) {
  if (text === void 0) {
    text = "";
  }
  if (asBlock === void 0) {
    asBlock = false;
  }
  return asBlock ? (openBlock(), createBlock(Comment, null, text)) : createVNode(Comment, null, text);
}
function normalizeVNode(child) {
  if (child == null || typeof child === "boolean") {
    return createVNode(Comment);
  } else if (isArray(child)) {
    return createVNode(Fragment, null,
    // #3666, avoid reference pollution when reusing vnode
    child.slice());
  } else if (isVNode(child)) {
    return cloneIfMounted(child);
  } else {
    return createVNode(Text, null, String(child));
  }
}
function cloneIfMounted(child) {
  return child.el === null && child.patchFlag !== -1 || child.memo ? child : cloneVNode(child);
}
function normalizeChildren(vnode, children) {
  let type = 0;
  const shapeFlag = vnode.shapeFlag;
  if (children == null) {
    children = null;
  } else if (isArray(children)) {
    type = 16;
  } else if (typeof children === "object") {
    if (shapeFlag & (1 | 64)) {
      const slot = children.default;
      if (slot) {
        slot._c && (slot._d = false);
        normalizeChildren(vnode, slot());
        slot._c && (slot._d = true);
      }
      return;
    } else {
      type = 32;
      const slotFlag = children._;
      if (!slotFlag && !isInternalObject(children)) {
        children._ctx = currentRenderingInstance;
      } else if (slotFlag === 3 && currentRenderingInstance) {
        if (currentRenderingInstance.slots._ === 1) {
          children._ = 1;
        } else {
          children._ = 2;
          vnode.patchFlag |= 1024;
        }
      }
    }
  } else if (isFunction(children)) {
    children = {
      default: children,
      _ctx: currentRenderingInstance
    };
    type = 32;
  } else {
    children = String(children);
    if (shapeFlag & 64) {
      type = 16;
      children = [createTextVNode(children)];
    } else {
      type = 8;
    }
  }
  vnode.children = children;
  vnode.shapeFlag |= type;
}
function mergeProps() {
  const ret = {};
  for (let i = 0; i < arguments.length; i++) {
    const toMerge = i < 0 || arguments.length <= i ? undefined : arguments[i];
    for (const key in toMerge) {
      if (key === "class") {
        if (ret.class !== toMerge.class) {
          ret.class = normalizeClass([ret.class, toMerge.class]);
        }
      } else if (key === "style") {
        ret.style = normalizeStyle([ret.style, toMerge.style]);
      } else if (isOn(key)) {
        const existing = ret[key];
        const incoming = toMerge[key];
        if (incoming && existing !== incoming && !(isArray(existing) && existing.includes(incoming))) {
          ret[key] = existing ? [].concat(existing, incoming) : incoming;
        }
      } else if (key !== "") {
        ret[key] = toMerge[key];
      }
    }
  }
  return ret;
}
function invokeVNodeHook(hook, instance, vnode, prevVNode) {
  if (prevVNode === void 0) {
    prevVNode = null;
  }
  callWithAsyncErrorHandling(hook, instance, 7, [vnode, prevVNode]);
}
const emptyAppContext = createAppContext();
let uid = 0;
function createComponentInstance(vnode, parent, suspense) {
  const type = vnode.type;
  const appContext = (parent ? parent.appContext : vnode.appContext) || emptyAppContext;
  const instance = {
    uid: uid++,
    vnode,
    type,
    parent,
    appContext,
    root: null,
    // to be immediately set
    next: null,
    subTree: null,
    // will be set synchronously right after creation
    effect: null,
    update: null,
    // will be set synchronously right after creation
    job: null,
    scope: new EffectScope(true
    /* detached */),
    render: null,
    proxy: null,
    exposed: null,
    exposeProxy: null,
    withProxy: null,
    provides: parent ? parent.provides : Object.create(appContext.provides),
    ids: parent ? parent.ids : ["", 0, 0],
    accessCache: null,
    renderCache: [],
    // local resolved assets
    components: null,
    directives: null,
    // resolved props and emits options
    propsOptions: normalizePropsOptions(type, appContext),
    emitsOptions: normalizeEmitsOptions(type, appContext),
    // emit
    emit: null,
    // to be set immediately
    emitted: null,
    // props default value
    propsDefaults: EMPTY_OBJ,
    // inheritAttrs
    inheritAttrs: type.inheritAttrs,
    // state
    ctx: EMPTY_OBJ,
    data: EMPTY_OBJ,
    props: EMPTY_OBJ,
    attrs: EMPTY_OBJ,
    slots: EMPTY_OBJ,
    refs: EMPTY_OBJ,
    setupState: EMPTY_OBJ,
    setupContext: null,
    // suspense related
    suspense,
    suspenseId: suspense ? suspense.pendingId : 0,
    asyncDep: null,
    asyncResolved: false,
    // lifecycle hooks
    // not using enums here because it results in computed properties
    isMounted: false,
    isUnmounted: false,
    isDeactivated: false,
    bc: null,
    c: null,
    bm: null,
    m: null,
    bu: null,
    u: null,
    um: null,
    bum: null,
    da: null,
    a: null,
    rtg: null,
    rtc: null,
    ec: null,
    sp: null
  };
  {
    instance.ctx = {
      _: instance
    };
  }
  instance.root = parent ? parent.root : instance;
  instance.emit = emit.bind(null, instance);
  if (vnode.ce) {
    vnode.ce(instance);
  }
  return instance;
}
let currentInstance = null;
const getCurrentInstance = () => currentInstance || currentRenderingInstance;
let internalSetCurrentInstance;
let setInSSRSetupState;
{
  const g = getGlobalThis();
  const registerGlobalSetter = (key, setter) => {
    let setters;
    if (!(setters = g[key])) setters = g[key] = [];
    setters.push(setter);
    return v => {
      if (setters.length > 1) setters.forEach(set => set(v));else setters[0](v);
    };
  };
  internalSetCurrentInstance = registerGlobalSetter("__VUE_INSTANCE_SETTERS__", v => currentInstance = v);
  setInSSRSetupState = registerGlobalSetter("__VUE_SSR_SETTERS__", v => isInSSRComponentSetup = v);
}
const setCurrentInstance = instance => {
  const prev = currentInstance;
  internalSetCurrentInstance(instance);
  instance.scope.on();
  return () => {
    instance.scope.off();
    internalSetCurrentInstance(prev);
  };
};
const unsetCurrentInstance = () => {
  currentInstance && currentInstance.scope.off();
  internalSetCurrentInstance(null);
};
function isStatefulComponent(instance) {
  return instance.vnode.shapeFlag & 4;
}
let isInSSRComponentSetup = false;
function setupComponent(instance, isSSR, optimized) {
  if (isSSR === void 0) {
    isSSR = false;
  }
  if (optimized === void 0) {
    optimized = false;
  }
  isSSR && setInSSRSetupState(isSSR);
  const _instance$vnode = instance.vnode,
    props = _instance$vnode.props,
    children = _instance$vnode.children;
  const isStateful = isStatefulComponent(instance);
  initProps(instance, props, isStateful, isSSR);
  initSlots(instance, children, optimized || isSSR);
  const setupResult = isStateful ? setupStatefulComponent(instance, isSSR) : void 0;
  isSSR && setInSSRSetupState(false);
  return setupResult;
}
function setupStatefulComponent(instance, isSSR) {
  const Component = instance.type;
  instance.accessCache = /* @__PURE__ */Object.create(null);
  instance.proxy = new Proxy(instance.ctx, PublicInstanceProxyHandlers);
  const setup = Component.setup;
  if (setup) {
    pauseTracking();
    const setupContext = instance.setupContext = setup.length > 1 ? createSetupContext(instance) : null;
    const reset = setCurrentInstance(instance);
    const setupResult = callWithErrorHandling(setup, instance, 0, [instance.props, setupContext]);
    const isAsyncSetup = isPromise$1(setupResult);
    resetTracking();
    reset();
    if ((isAsyncSetup || instance.sp) && !isAsyncWrapper(instance)) {
      markAsyncBoundary(instance);
    }
    if (isAsyncSetup) {
      setupResult.then(unsetCurrentInstance, unsetCurrentInstance);
      if (isSSR) {
        return setupResult.then(resolvedResult => {
          handleSetupResult(instance, resolvedResult, isSSR);
        }).catch(e => {
          handleError(e, instance, 0);
        });
      } else {
        instance.asyncDep = setupResult;
      }
    } else {
      handleSetupResult(instance, setupResult, isSSR);
    }
  } else {
    finishComponentSetup(instance, isSSR);
  }
}
function handleSetupResult(instance, setupResult, isSSR) {
  if (isFunction(setupResult)) {
    if (instance.type.__ssrInlineRender) {
      instance.ssrRender = setupResult;
    } else {
      instance.render = setupResult;
    }
  } else if (isObject$2(setupResult)) {
    instance.setupState = proxyRefs(setupResult);
  } else ;
  finishComponentSetup(instance, isSSR);
}
let compile;
function finishComponentSetup(instance, isSSR, skipOptions) {
  const Component = instance.type;
  if (!instance.render) {
    if (!isSSR && compile && !Component.render) {
      const template = Component.template || resolveMergedOptions(instance).template;
      if (template) {
        const _instance$appContext$ = instance.appContext.config,
          isCustomElement = _instance$appContext$.isCustomElement,
          compilerOptions = _instance$appContext$.compilerOptions;
        const delimiters = Component.delimiters,
          componentCompilerOptions = Component.compilerOptions;
        const finalCompilerOptions = extend$2(extend$2({
          isCustomElement,
          delimiters
        }, compilerOptions), componentCompilerOptions);
        Component.render = compile(template, finalCompilerOptions);
      }
    }
    instance.render = Component.render || NOOP;
  }
  {
    const reset = setCurrentInstance(instance);
    pauseTracking();
    try {
      applyOptions(instance);
    } finally {
      resetTracking();
      reset();
    }
  }
}
const attrsProxyHandlers = {
  get(target, key) {
    track(target, "get", "");
    return target[key];
  }
};
function createSetupContext(instance) {
  const expose = exposed => {
    instance.exposed = exposed || {};
  };
  {
    return {
      attrs: new Proxy(instance.attrs, attrsProxyHandlers),
      slots: instance.slots,
      emit: instance.emit,
      expose
    };
  }
}
function getComponentPublicInstance(instance) {
  if (instance.exposed) {
    return instance.exposeProxy || (instance.exposeProxy = new Proxy(proxyRefs(markRaw(instance.exposed)), {
      get(target, key) {
        if (key in target) {
          return target[key];
        } else if (key in publicPropertiesMap) {
          return publicPropertiesMap[key](instance);
        }
      },
      has(target, key) {
        return key in target || key in publicPropertiesMap;
      }
    }));
  } else {
    return instance.proxy;
  }
}
const classifyRE = /(?:^|[-_])\w/g;
const classify = str => str.replace(classifyRE, c => c.toUpperCase()).replace(/[-_]/g, "");
function getComponentName(Component, includeInferred) {
  if (includeInferred === void 0) {
    includeInferred = true;
  }
  return isFunction(Component) ? Component.displayName || Component.name : Component.name || includeInferred && Component.__name;
}
function formatComponentName(instance, Component, isRoot) {
  if (isRoot === void 0) {
    isRoot = false;
  }
  let name = getComponentName(Component);
  if (!name && Component.__file) {
    const match = Component.__file.match(/([^/\\]+)\.\w+$/);
    if (match) {
      name = match[1];
    }
  }
  if (!name && instance) {
    const inferFromRegistry = registry => {
      for (const key in registry) {
        if (registry[key] === Component) {
          return key;
        }
      }
    };
    name = inferFromRegistry(instance.components) || instance.parent && inferFromRegistry(instance.parent.type.components) || inferFromRegistry(instance.appContext.components);
  }
  return name ? classify(name) : isRoot ? "App" : "Anonymous";
}
function isClassComponent(value) {
  return isFunction(value) && "__vccOpts" in value;
}
const computed = (getterOrOptions, debugOptions) => {
  const c = computed$1(getterOrOptions, debugOptions, isInSSRComponentSetup);
  return c;
};
function h(type, propsOrChildren, children) {
  try {
    setBlockTracking(-1);
    const l = arguments.length;
    if (l === 2) {
      if (isObject$2(propsOrChildren) && !isArray(propsOrChildren)) {
        if (isVNode(propsOrChildren)) {
          return createVNode(type, null, [propsOrChildren]);
        }
        return createVNode(type, propsOrChildren);
      } else {
        return createVNode(type, null, propsOrChildren);
      }
    } else {
      if (l > 3) {
        children = Array.prototype.slice.call(arguments, 2);
      } else if (l === 3 && isVNode(children)) {
        children = [children];
      }
      return createVNode(type, propsOrChildren, children);
    }
  } finally {
    setBlockTracking(1);
  }
}
function isMemoSame(cached, memo) {
  const prev = cached.memo;
  if (prev.length != memo.length) {
    return false;
  }
  for (let i = 0; i < prev.length; i++) {
    if (hasChanged(prev[i], memo[i])) {
      return false;
    }
  }
  if (isBlockTreeEnabled > 0 && currentBlock) {
    currentBlock.push(cached);
  }
  return true;
}
const version = "3.5.30";

/**
* @vue/runtime-dom v3.5.30
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
let policy = void 0;
const tt = typeof window !== "undefined" && window.trustedTypes;
if (tt) {
  try {
    policy = /* @__PURE__ */tt.createPolicy("vue", {
      createHTML: val => val
    });
  } catch (e) {
  }
}
const unsafeToTrustedHTML = policy ? val => policy.createHTML(val) : val => val;
const svgNS = "http://www.w3.org/2000/svg";
const mathmlNS = "http://www.w3.org/1998/Math/MathML";
const doc = typeof document !== "undefined" ? document : null;
const templateContainer = doc && /* @__PURE__ */doc.createElement("template");
const nodeOps = {
  insert: (child, parent, anchor) => {
    parent.insertBefore(child, anchor || null);
  },
  remove: child => {
    const parent = child.parentNode;
    if (parent) {
      parent.removeChild(child);
    }
  },
  createElement: (tag, namespace, is, props) => {
    const el = namespace === "svg" ? doc.createElementNS(svgNS, tag) : namespace === "mathml" ? doc.createElementNS(mathmlNS, tag) : is ? doc.createElement(tag, {
      is
    }) : doc.createElement(tag);
    if (tag === "select" && props && props.multiple != null) {
      el.setAttribute("multiple", props.multiple);
    }
    return el;
  },
  createText: text => doc.createTextNode(text),
  createComment: text => doc.createComment(text),
  setText: (node, text) => {
    node.nodeValue = text;
  },
  setElementText: (el, text) => {
    el.textContent = text;
  },
  parentNode: node => node.parentNode,
  nextSibling: node => node.nextSibling,
  querySelector: selector => doc.querySelector(selector),
  setScopeId(el, id) {
    el.setAttribute(id, "");
  },
  // __UNSAFE__
  // Reason: innerHTML.
  // Static content here can only come from compiled templates.
  // As long as the user only uses trusted templates, this is safe.
  insertStaticContent(content, parent, anchor, namespace, start, end) {
    const before = anchor ? anchor.previousSibling : parent.lastChild;
    if (start && (start === end || start.nextSibling)) {
      while (true) {
        parent.insertBefore(start.cloneNode(true), anchor);
        if (start === end || !(start = start.nextSibling)) break;
      }
    } else {
      templateContainer.innerHTML = unsafeToTrustedHTML(namespace === "svg" ? "<svg>" + content + "</svg>" : namespace === "mathml" ? "<math>" + content + "</math>" : content);
      const template = templateContainer.content;
      if (namespace === "svg" || namespace === "mathml") {
        const wrapper = template.firstChild;
        while (wrapper.firstChild) {
          template.appendChild(wrapper.firstChild);
        }
        template.removeChild(wrapper);
      }
      parent.insertBefore(template, anchor);
    }
    return [
    // first
    before ? before.nextSibling : parent.firstChild,
    // last
    anchor ? anchor.previousSibling : parent.lastChild];
  }
};
const vtcKey = /* @__PURE__ */Symbol("_vtc");
function patchClass(el, value, isSVG) {
  const transitionClasses = el[vtcKey];
  if (transitionClasses) {
    value = (value ? [value, ...transitionClasses] : [...transitionClasses]).join(" ");
  }
  if (value == null) {
    el.removeAttribute("class");
  } else if (isSVG) {
    el.setAttribute("class", value);
  } else {
    el.className = value;
  }
}
const vShowOriginalDisplay = /* @__PURE__ */Symbol("_vod");
const vShowHidden = /* @__PURE__ */Symbol("_vsh");
const CSS_VAR_TEXT = /* @__PURE__ */Symbol("");
const displayRE = /(?:^|;)\s*display\s*:/;
function patchStyle(el, prev, next) {
  const style = el.style;
  const isCssString = isString(next);
  let hasControlledDisplay = false;
  if (next && !isCssString) {
    if (prev) {
      if (!isString(prev)) {
        for (const key in prev) {
          if (next[key] == null) {
            setStyle(style, key, "");
          }
        }
      } else {
        for (const prevStyle of prev.split(";")) {
          const key = prevStyle.slice(0, prevStyle.indexOf(":")).trim();
          if (next[key] == null) {
            setStyle(style, key, "");
          }
        }
      }
    }
    for (const key in next) {
      if (key === "display") {
        hasControlledDisplay = true;
      }
      setStyle(style, key, next[key]);
    }
  } else {
    if (isCssString) {
      if (prev !== next) {
        const cssVarText = style[CSS_VAR_TEXT];
        if (cssVarText) {
          next += ";" + cssVarText;
        }
        style.cssText = next;
        hasControlledDisplay = displayRE.test(next);
      }
    } else if (prev) {
      el.removeAttribute("style");
    }
  }
  if (vShowOriginalDisplay in el) {
    el[vShowOriginalDisplay] = hasControlledDisplay ? style.display : "";
    if (el[vShowHidden]) {
      style.display = "none";
    }
  }
}
const importantRE = /\s*!important$/;
function setStyle(style, name, val) {
  if (isArray(val)) {
    val.forEach(v => setStyle(style, name, v));
  } else {
    if (val == null) val = "";
    if (name.startsWith("--")) {
      style.setProperty(name, val);
    } else {
      const prefixed = autoPrefix(style, name);
      if (importantRE.test(val)) {
        style.setProperty(hyphenate(prefixed), val.replace(importantRE, ""), "important");
      } else {
        style[prefixed] = val;
      }
    }
  }
}
const prefixes = ["Webkit", "Moz", "ms"];
const prefixCache = {};
function autoPrefix(style, rawName) {
  const cached = prefixCache[rawName];
  if (cached) {
    return cached;
  }
  let name = camelize(rawName);
  if (name !== "filter" && name in style) {
    return prefixCache[rawName] = name;
  }
  name = capitalize(name);
  for (let i = 0; i < prefixes.length; i++) {
    const prefixed = prefixes[i] + name;
    if (prefixed in style) {
      return prefixCache[rawName] = prefixed;
    }
  }
  return rawName;
}
const xlinkNS = "http://www.w3.org/1999/xlink";
function patchAttr(el, key, value, isSVG, instance, isBoolean) {
  if (isBoolean === void 0) {
    isBoolean = isSpecialBooleanAttr(key);
  }
  if (isSVG && key.startsWith("xlink:")) {
    if (value == null) {
      el.removeAttributeNS(xlinkNS, key.slice(6, key.length));
    } else {
      el.setAttributeNS(xlinkNS, key, value);
    }
  } else {
    if (value == null || isBoolean && !includeBooleanAttr(value)) {
      el.removeAttribute(key);
    } else {
      el.setAttribute(key, isBoolean ? "" : isSymbol(value) ? String(value) : value);
    }
  }
}
function patchDOMProp(el, key, value, parentComponent, attrName) {
  if (key === "innerHTML" || key === "textContent") {
    if (value != null) {
      el[key] = key === "innerHTML" ? unsafeToTrustedHTML(value) : value;
    }
    return;
  }
  const tag = el.tagName;
  if (key === "value" && tag !== "PROGRESS" &&
  // custom elements may use _value internally
  !tag.includes("-")) {
    const oldValue = tag === "OPTION" ? el.getAttribute("value") || "" : el.value;
    const newValue = value == null ?
    // #11647: value should be set as empty string for null and undefined,
    // but <input type="checkbox"> should be set as 'on'.
    el.type === "checkbox" ? "on" : "" : String(value);
    if (oldValue !== newValue || !("_value" in el)) {
      el.value = newValue;
    }
    if (value == null) {
      el.removeAttribute(key);
    }
    el._value = value;
    return;
  }
  let needRemove = false;
  if (value === "" || value == null) {
    const type = typeof el[key];
    if (type === "boolean") {
      value = includeBooleanAttr(value);
    } else if (value == null && type === "string") {
      value = "";
      needRemove = true;
    } else if (type === "number") {
      value = 0;
      needRemove = true;
    }
  }
  try {
    el[key] = value;
  } catch (e) {
  }
  needRemove && el.removeAttribute(attrName || key);
}
function addEventListener(el, event, handler, options) {
  el.addEventListener(event, handler, options);
}
function removeEventListener(el, event, handler, options) {
  el.removeEventListener(event, handler, options);
}
const veiKey = /* @__PURE__ */Symbol("_vei");
function patchEvent(el, rawName, prevValue, nextValue, instance) {
  if (instance === void 0) {
    instance = null;
  }
  const invokers = el[veiKey] || (el[veiKey] = {});
  const existingInvoker = invokers[rawName];
  if (nextValue && existingInvoker) {
    existingInvoker.value = nextValue;
  } else {
    const _parseName = parseName(rawName),
      name = _parseName[0],
      options = _parseName[1];
    if (nextValue) {
      const invoker = invokers[rawName] = createInvoker(nextValue, instance);
      addEventListener(el, name, invoker, options);
    } else if (existingInvoker) {
      removeEventListener(el, name, existingInvoker, options);
      invokers[rawName] = void 0;
    }
  }
}
const optionsModifierRE = /(?:Once|Passive|Capture)$/;
function parseName(name) {
  let options;
  if (optionsModifierRE.test(name)) {
    options = {};
    let m;
    while (m = name.match(optionsModifierRE)) {
      name = name.slice(0, name.length - m[0].length);
      options[m[0].toLowerCase()] = true;
    }
  }
  const event = name[2] === ":" ? name.slice(3) : hyphenate(name.slice(2));
  return [event, options];
}
let cachedNow = 0;
const p = /* @__PURE__ */Promise.resolve();
const getNow = () => cachedNow || (p.then(() => cachedNow = 0), cachedNow = Date.now());
function createInvoker(initialValue, instance) {
  const invoker = e => {
    if (!e._vts) {
      e._vts = Date.now();
    } else if (e._vts <= invoker.attached) {
      return;
    }
    callWithAsyncErrorHandling(patchStopImmediatePropagation(e, invoker.value), instance, 5, [e]);
  };
  invoker.value = initialValue;
  invoker.attached = getNow();
  return invoker;
}
function patchStopImmediatePropagation(e, value) {
  if (isArray(value)) {
    const originalStop = e.stopImmediatePropagation;
    e.stopImmediatePropagation = () => {
      originalStop.call(e);
      e._stopped = true;
    };
    return value.map(fn => e2 => !e2._stopped && fn && fn(e2));
  } else {
    return value;
  }
}
const isNativeOn = key => key.charCodeAt(0) === 111 && key.charCodeAt(1) === 110 &&
// lowercase letter
key.charCodeAt(2) > 96 && key.charCodeAt(2) < 123;
const patchProp = (el, key, prevValue, nextValue, namespace, parentComponent) => {
  const isSVG = namespace === "svg";
  if (key === "class") {
    patchClass(el, nextValue, isSVG);
  } else if (key === "style") {
    patchStyle(el, prevValue, nextValue);
  } else if (isOn(key)) {
    if (!isModelListener(key)) {
      patchEvent(el, key, prevValue, nextValue, parentComponent);
    }
  } else if (key[0] === "." ? (key = key.slice(1), true) : key[0] === "^" ? (key = key.slice(1), false) : shouldSetAsProp(el, key, nextValue, isSVG)) {
    patchDOMProp(el, key, nextValue);
    if (!el.tagName.includes("-") && (key === "value" || key === "checked" || key === "selected")) {
      patchAttr(el, key, nextValue, isSVG, parentComponent, key !== "value");
    }
  } else if (
  // #11081 force set props for possible async custom element
  el._isVueCE && (
  // #12408 check if it's declared prop or it's async custom element
  shouldSetAsPropForVueCE(el, key) ||
  // @ts-expect-error _def is private
  el._def.__asyncLoader && (/[A-Z]/.test(key) || !isString(nextValue)))) {
    patchDOMProp(el, camelize(key), nextValue, parentComponent, key);
  } else {
    if (key === "true-value") {
      el._trueValue = nextValue;
    } else if (key === "false-value") {
      el._falseValue = nextValue;
    }
    patchAttr(el, key, nextValue, isSVG);
  }
};
function shouldSetAsProp(el, key, value, isSVG) {
  if (isSVG) {
    if (key === "innerHTML" || key === "textContent") {
      return true;
    }
    if (key in el && isNativeOn(key) && isFunction(value)) {
      return true;
    }
    return false;
  }
  if (key === "spellcheck" || key === "draggable" || key === "translate" || key === "autocorrect") {
    return false;
  }
  if (key === "sandbox" && el.tagName === "IFRAME") {
    return false;
  }
  if (key === "form") {
    return false;
  }
  if (key === "list" && el.tagName === "INPUT") {
    return false;
  }
  if (key === "type" && el.tagName === "TEXTAREA") {
    return false;
  }
  if (key === "width" || key === "height") {
    const tag = el.tagName;
    if (tag === "IMG" || tag === "VIDEO" || tag === "CANVAS" || tag === "SOURCE") {
      return false;
    }
  }
  if (isNativeOn(key) && isString(value)) {
    return false;
  }
  return key in el;
}
function shouldSetAsPropForVueCE(el, key) {
  const props =
  // @ts-expect-error _def is private
  el._def.props;
  if (!props) {
    return false;
  }
  const camelKey = camelize(key);
  return Array.isArray(props) ? props.some(prop => camelize(prop) === camelKey) : Object.keys(props).some(prop => camelize(prop) === camelKey);
}
const systemModifiers = ["ctrl", "shift", "alt", "meta"];
const modifierGuards = {
  stop: e => e.stopPropagation(),
  prevent: e => e.preventDefault(),
  self: e => e.target !== e.currentTarget,
  ctrl: e => !e.ctrlKey,
  shift: e => !e.shiftKey,
  alt: e => !e.altKey,
  meta: e => !e.metaKey,
  left: e => "button" in e && e.button !== 0,
  middle: e => "button" in e && e.button !== 1,
  right: e => "button" in e && e.button !== 2,
  exact: (e, modifiers) => systemModifiers.some(m => e[m + "Key"] && !modifiers.includes(m))
};
const withModifiers = (fn, modifiers) => {
  if (!fn) return fn;
  const cache = fn._withMods || (fn._withMods = {});
  const cacheKey = modifiers.join(".");
  return cache[cacheKey] || (cache[cacheKey] = function (event) {
    for (let i = 0; i < modifiers.length; i++) {
      const guard = modifierGuards[modifiers[i]];
      if (guard && guard(event, modifiers)) return;
    }
    for (var _len2 = arguments.length, args = new Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
      args[_key2 - 1] = arguments[_key2];
    }
    return fn(event, ...args);
  });
};
const keyNames = {
  esc: "escape",
  space: " ",
  up: "arrow-up",
  left: "arrow-left",
  right: "arrow-right",
  down: "arrow-down",
  delete: "backspace"
};
const withKeys = (fn, modifiers) => {
  const cache = fn._withKeys || (fn._withKeys = {});
  const cacheKey = modifiers.join(".");
  return cache[cacheKey] || (cache[cacheKey] = event => {
    if (!("key" in event)) {
      return;
    }
    const eventKey = hyphenate(event.key);
    if (modifiers.some(k => k === eventKey || keyNames[k] === eventKey)) {
      return fn(event);
    }
  });
};
const rendererOptions = /* @__PURE__ */extend$2({
  patchProp
}, nodeOps);
let renderer;
function ensureRenderer() {
  return renderer || (renderer = createRenderer(rendererOptions));
}
const createApp = function createApp() {
  const app = ensureRenderer().createApp(...arguments);
  const mount = app.mount;
  app.mount = containerOrSelector => {
    const container = normalizeContainer(containerOrSelector);
    if (!container) return;
    const component = app._component;
    if (!isFunction(component) && !component.render && !component.template) {
      component.template = container.innerHTML;
    }
    if (container.nodeType === 1) {
      container.textContent = "";
    }
    const proxy = mount(container, false, resolveRootNamespace(container));
    if (container instanceof Element) {
      container.removeAttribute("v-cloak");
      container.setAttribute("data-v-app", "");
    }
    return proxy;
  };
  return app;
};
function resolveRootNamespace(container) {
  if (container instanceof SVGElement) {
    return "svg";
  }
  if (typeof MathMLElement === "function" && container instanceof MathMLElement) {
    return "mathml";
  }
}
function normalizeContainer(container) {
  if (isString(container)) {
    const res = document.querySelector(container);
    return res;
  }
  return container;
}

function getDevtoolsGlobalHook() {
  return getTarget().__VUE_DEVTOOLS_GLOBAL_HOOK__;
}
function getTarget() {
  // @ts-expect-error navigator and windows are not available in all environments
  return typeof navigator !== 'undefined' && typeof window !== 'undefined' ? window : typeof globalThis !== 'undefined' ? globalThis : {};
}
const isProxyAvailable = typeof Proxy === 'function';

const HOOK_SETUP = 'devtools-plugin:setup';
const HOOK_PLUGIN_SETTINGS_SET = 'plugin:settings:set';

let supported;
let perf;
function isPerformanceSupported() {
  var _a;
  if (supported !== undefined) {
    return supported;
  }
  if (typeof window !== 'undefined' && window.performance) {
    supported = true;
    perf = window.performance;
  } else if (typeof globalThis !== 'undefined' && ((_a = globalThis.perf_hooks) === null || _a === void 0 ? void 0 : _a.performance)) {
    supported = true;
    perf = globalThis.perf_hooks.performance;
  } else {
    supported = false;
  }
  return supported;
}
function now$2() {
  return isPerformanceSupported() ? perf.now() : Date.now();
}

class ApiProxy {
  constructor(plugin, hook) {
    var _this = this;
    this.target = null;
    this.targetQueue = [];
    this.onQueue = [];
    this.plugin = plugin;
    this.hook = hook;
    const defaultSettings = {};
    if (plugin.settings) {
      for (const id in plugin.settings) {
        const item = plugin.settings[id];
        defaultSettings[id] = item.defaultValue;
      }
    }
    const localSettingsSaveId = "__vue-devtools-plugin-settings__" + plugin.id;
    let currentSettings = Object.assign({}, defaultSettings);
    try {
      const raw = localStorage.getItem(localSettingsSaveId);
      const data = JSON.parse(raw);
      Object.assign(currentSettings, data);
    } catch (e) {
      // noop
    }
    this.fallbacks = {
      getSettings() {
        return currentSettings;
      },
      setSettings(value) {
        try {
          localStorage.setItem(localSettingsSaveId, JSON.stringify(value));
        } catch (e) {
          // noop
        }
        currentSettings = value;
      },
      now() {
        return now$2();
      }
    };
    if (hook) {
      hook.on(HOOK_PLUGIN_SETTINGS_SET, (pluginId, value) => {
        if (pluginId === this.plugin.id) {
          this.fallbacks.setSettings(value);
        }
      });
    }
    this.proxiedOn = new Proxy({}, {
      get: (_target, prop) => {
        if (this.target) {
          return this.target.on[prop];
        } else {
          return function () {
            for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
              args[_key] = arguments[_key];
            }
            _this.onQueue.push({
              method: prop,
              args
            });
          };
        }
      }
    });
    this.proxiedTarget = new Proxy({}, {
      get: (_target, prop) => {
        if (this.target) {
          return this.target[prop];
        } else if (prop === 'on') {
          return this.proxiedOn;
        } else if (Object.keys(this.fallbacks).includes(prop)) {
          return function () {
            for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
              args[_key2] = arguments[_key2];
            }
            _this.targetQueue.push({
              method: prop,
              args,
              resolve: () => {}
            });
            return _this.fallbacks[prop](...args);
          };
        } else {
          return function () {
            for (var _len3 = arguments.length, args = new Array(_len3), _key3 = 0; _key3 < _len3; _key3++) {
              args[_key3] = arguments[_key3];
            }
            return new Promise(resolve => {
              _this.targetQueue.push({
                method: prop,
                args,
                resolve
              });
            });
          };
        }
      }
    });
  }
  async setRealTarget(target) {
    this.target = target;
    for (const item of this.onQueue) {
      this.target.on[item.method](...item.args);
    }
    for (const item of this.targetQueue) {
      item.resolve(await this.target[item.method](...item.args));
    }
  }
}

function setupDevtoolsPlugin(pluginDescriptor, setupFn) {
  const descriptor = pluginDescriptor;
  const target = getTarget();
  const hook = getDevtoolsGlobalHook();
  const enableProxy = isProxyAvailable && descriptor.enableEarlyProxy;
  if (hook && (target.__VUE_DEVTOOLS_PLUGIN_API_AVAILABLE__ || !enableProxy)) {
    hook.emit(HOOK_SETUP, pluginDescriptor, setupFn);
  } else {
    const proxy = enableProxy ? new ApiProxy(descriptor, hook) : null;
    const list = target.__VUE_DEVTOOLS_PLUGINS__ = target.__VUE_DEVTOOLS_PLUGINS__ || [];
    list.push({
      pluginDescriptor: descriptor,
      setupFn,
      proxy
    });
    if (proxy) {
      setupFn(proxy.proxiedTarget);
    }
  }
}

/*!
 * vuex v4.1.0
 * (c) 2022 Evan You
 * @license MIT
 */
var storeKey = 'store';
function useStore(key) {
  if (key === void 0) key = null;
  return inject(key !== null ? key : storeKey);
}

/**
 * forEach for object
 */
function forEachValue(obj, fn) {
  Object.keys(obj).forEach(function (key) {
    return fn(obj[key], key);
  });
}
function isObject$1(obj) {
  return obj !== null && typeof obj === 'object';
}
function isPromise(val) {
  return val && typeof val.then === 'function';
}
function partial(fn, arg) {
  return function () {
    return fn(arg);
  };
}
function genericSubscribe(fn, subs, options) {
  if (subs.indexOf(fn) < 0) {
    options && options.prepend ? subs.unshift(fn) : subs.push(fn);
  }
  return function () {
    var i = subs.indexOf(fn);
    if (i > -1) {
      subs.splice(i, 1);
    }
  };
}
function resetStore(store, hot) {
  store._actions = Object.create(null);
  store._mutations = Object.create(null);
  store._wrappedGetters = Object.create(null);
  store._modulesNamespaceMap = Object.create(null);
  var state = store.state;
  // init all modules
  installModule(store, state, [], store._modules.root, true);
  // reset state
  resetStoreState(store, state, hot);
}
function resetStoreState(store, state, hot) {
  var oldState = store._state;
  var oldScope = store._scope;

  // bind store public getters
  store.getters = {};
  // reset local getters cache
  store._makeLocalGettersCache = Object.create(null);
  var wrappedGetters = store._wrappedGetters;
  var computedObj = {};
  var computedCache = {};

  // create a new effect scope and create computed object inside it to avoid
  // getters (computed) getting destroyed on component unmount.
  var scope = effectScope(true);
  scope.run(function () {
    forEachValue(wrappedGetters, function (fn, key) {
      // use computed to leverage its lazy-caching mechanism
      // direct inline function use will lead to closure preserving oldState.
      // using partial to return function with only arguments preserved in closure environment.
      computedObj[key] = partial(fn, store);
      computedCache[key] = computed(function () {
        return computedObj[key]();
      });
      Object.defineProperty(store.getters, key, {
        get: function get() {
          return computedCache[key].value;
        },
        enumerable: true // for local getters
      });
    });
  });
  store._state = reactive({
    data: state
  });

  // register the newly created effect scope to the store so that we can
  // dispose the effects when this method runs again in the future.
  store._scope = scope;

  // enable strict mode for new state
  if (store.strict) {
    enableStrictMode(store);
  }
  if (oldState) {
    if (hot) {
      // dispatch changes in all subscribed watchers
      // to force getter re-evaluation for hot reloading.
      store._withCommit(function () {
        oldState.data = null;
      });
    }
  }

  // dispose previously registered effect scope if there is one.
  if (oldScope) {
    oldScope.stop();
  }
}
function installModule(store, rootState, path, module, hot) {
  var isRoot = !path.length;
  var namespace = store._modules.getNamespace(path);

  // register in namespace map
  if (module.namespaced) {
    if (store._modulesNamespaceMap[namespace] && "production" !== 'production') ;
    store._modulesNamespaceMap[namespace] = module;
  }

  // set state
  if (!isRoot && !hot) {
    var parentState = getNestedState(rootState, path.slice(0, -1));
    var moduleName = path[path.length - 1];
    store._withCommit(function () {
      parentState[moduleName] = module.state;
    });
  }
  var local = module.context = makeLocalContext(store, namespace, path);
  module.forEachMutation(function (mutation, key) {
    var namespacedType = namespace + key;
    registerMutation(store, namespacedType, mutation, local);
  });
  module.forEachAction(function (action, key) {
    var type = action.root ? key : namespace + key;
    var handler = action.handler || action;
    registerAction(store, type, handler, local);
  });
  module.forEachGetter(function (getter, key) {
    var namespacedType = namespace + key;
    registerGetter(store, namespacedType, getter, local);
  });
  module.forEachChild(function (child, key) {
    installModule(store, rootState, path.concat(key), child, hot);
  });
}

/**
 * make localized dispatch, commit, getters and state
 * if there is no namespace, just use root ones
 */
function makeLocalContext(store, namespace, path) {
  var noNamespace = namespace === '';
  var local = {
    dispatch: noNamespace ? store.dispatch : function (_type, _payload, _options) {
      var args = unifyObjectStyle(_type, _payload, _options);
      var payload = args.payload;
      var options = args.options;
      var type = args.type;
      if (!options || !options.root) {
        type = namespace + type;
      }
      return store.dispatch(type, payload);
    },
    commit: noNamespace ? store.commit : function (_type, _payload, _options) {
      var args = unifyObjectStyle(_type, _payload, _options);
      var payload = args.payload;
      var options = args.options;
      var type = args.type;
      if (!options || !options.root) {
        type = namespace + type;
      }
      store.commit(type, payload, options);
    }
  };

  // getters and state object must be gotten lazily
  // because they will be changed by state update
  Object.defineProperties(local, {
    getters: {
      get: noNamespace ? function () {
        return store.getters;
      } : function () {
        return makeLocalGetters(store, namespace);
      }
    },
    state: {
      get: function get() {
        return getNestedState(store.state, path);
      }
    }
  });
  return local;
}
function makeLocalGetters(store, namespace) {
  if (!store._makeLocalGettersCache[namespace]) {
    var gettersProxy = {};
    var splitPos = namespace.length;
    Object.keys(store.getters).forEach(function (type) {
      // skip if the target getter is not match this namespace
      if (type.slice(0, splitPos) !== namespace) {
        return;
      }

      // extract local getter type
      var localType = type.slice(splitPos);

      // Add a port to the getters proxy.
      // Define as getter property because
      // we do not want to evaluate the getters in this time.
      Object.defineProperty(gettersProxy, localType, {
        get: function get() {
          return store.getters[type];
        },
        enumerable: true
      });
    });
    store._makeLocalGettersCache[namespace] = gettersProxy;
  }
  return store._makeLocalGettersCache[namespace];
}
function registerMutation(store, type, handler, local) {
  var entry = store._mutations[type] || (store._mutations[type] = []);
  entry.push(function wrappedMutationHandler(payload) {
    handler.call(store, local.state, payload);
  });
}
function registerAction(store, type, handler, local) {
  var entry = store._actions[type] || (store._actions[type] = []);
  entry.push(function wrappedActionHandler(payload) {
    var res = handler.call(store, {
      dispatch: local.dispatch,
      commit: local.commit,
      getters: local.getters,
      state: local.state,
      rootGetters: store.getters,
      rootState: store.state
    }, payload);
    if (!isPromise(res)) {
      res = Promise.resolve(res);
    }
    if (store._devtoolHook) {
      return res.catch(function (err) {
        store._devtoolHook.emit('vuex:error', err);
        throw err;
      });
    } else {
      return res;
    }
  });
}
function registerGetter(store, type, rawGetter, local) {
  if (store._wrappedGetters[type]) {
    return;
  }
  store._wrappedGetters[type] = function wrappedGetter(store) {
    return rawGetter(local.state,
    // local state
    local.getters,
    // local getters
    store.state,
    // root state
    store.getters // root getters
    );
  };
}
function enableStrictMode(store) {
  watch(function () {
    return store._state.data;
  }, function () {
  }, {
    deep: true,
    flush: 'sync'
  });
}
function getNestedState(state, path) {
  return path.reduce(function (state, key) {
    return state[key];
  }, state);
}
function unifyObjectStyle(type, payload, options) {
  if (isObject$1(type) && type.type) {
    options = payload;
    payload = type;
    type = type.type;
  }
  return {
    type: type,
    payload: payload,
    options: options
  };
}
var LABEL_VUEX_BINDINGS = 'vuex bindings';
var MUTATIONS_LAYER_ID = 'vuex:mutations';
var ACTIONS_LAYER_ID = 'vuex:actions';
var INSPECTOR_ID = 'vuex';
var actionId = 0;
function addDevtools(app, store) {
  setupDevtoolsPlugin({
    id: 'org.vuejs.vuex',
    app: app,
    label: 'Vuex',
    homepage: 'https://next.vuex.vuejs.org/',
    logo: 'https://vuejs.org/images/icons/favicon-96x96.png',
    packageName: 'vuex',
    componentStateTypes: [LABEL_VUEX_BINDINGS]
  }, function (api) {
    api.addTimelineLayer({
      id: MUTATIONS_LAYER_ID,
      label: 'Vuex Mutations',
      color: COLOR_LIME_500
    });
    api.addTimelineLayer({
      id: ACTIONS_LAYER_ID,
      label: 'Vuex Actions',
      color: COLOR_LIME_500
    });
    api.addInspector({
      id: INSPECTOR_ID,
      label: 'Vuex',
      icon: 'storage',
      treeFilterPlaceholder: 'Filter stores...'
    });
    api.on.getInspectorTree(function (payload) {
      if (payload.app === app && payload.inspectorId === INSPECTOR_ID) {
        if (payload.filter) {
          var nodes = [];
          flattenStoreForInspectorTree(nodes, store._modules.root, payload.filter, '');
          payload.rootNodes = nodes;
        } else {
          payload.rootNodes = [formatStoreForInspectorTree(store._modules.root, '')];
        }
      }
    });
    api.on.getInspectorState(function (payload) {
      if (payload.app === app && payload.inspectorId === INSPECTOR_ID) {
        var modulePath = payload.nodeId;
        makeLocalGetters(store, modulePath);
        payload.state = formatStoreForInspectorState(getStoreModule(store._modules, modulePath), modulePath === 'root' ? store.getters : store._makeLocalGettersCache, modulePath);
      }
    });
    api.on.editInspectorState(function (payload) {
      if (payload.app === app && payload.inspectorId === INSPECTOR_ID) {
        var modulePath = payload.nodeId;
        var path = payload.path;
        if (modulePath !== 'root') {
          path = modulePath.split('/').filter(Boolean).concat(path);
        }
        store._withCommit(function () {
          payload.set(store._state.data, path, payload.state.value);
        });
      }
    });
    store.subscribe(function (mutation, state) {
      var data = {};
      if (mutation.payload) {
        data.payload = mutation.payload;
      }
      data.state = state;
      api.notifyComponentUpdate();
      api.sendInspectorTree(INSPECTOR_ID);
      api.sendInspectorState(INSPECTOR_ID);
      api.addTimelineEvent({
        layerId: MUTATIONS_LAYER_ID,
        event: {
          time: Date.now(),
          title: mutation.type,
          data: data
        }
      });
    });
    store.subscribeAction({
      before: function before(action, state) {
        var data = {};
        if (action.payload) {
          data.payload = action.payload;
        }
        action._id = actionId++;
        action._time = Date.now();
        data.state = state;
        api.addTimelineEvent({
          layerId: ACTIONS_LAYER_ID,
          event: {
            time: action._time,
            title: action.type,
            groupId: action._id,
            subtitle: 'start',
            data: data
          }
        });
      },
      after: function after(action, state) {
        var data = {};
        var duration = Date.now() - action._time;
        data.duration = {
          _custom: {
            type: 'duration',
            display: duration + "ms",
            tooltip: 'Action duration',
            value: duration
          }
        };
        if (action.payload) {
          data.payload = action.payload;
        }
        data.state = state;
        api.addTimelineEvent({
          layerId: ACTIONS_LAYER_ID,
          event: {
            time: Date.now(),
            title: action.type,
            groupId: action._id,
            subtitle: 'end',
            data: data
          }
        });
      }
    });
  });
}

// extracted from tailwind palette
var COLOR_LIME_500 = 0x84cc16;
var COLOR_DARK = 0x666666;
var COLOR_WHITE = 0xffffff;
var TAG_NAMESPACED = {
  label: 'namespaced',
  textColor: COLOR_WHITE,
  backgroundColor: COLOR_DARK
};

/**
 * @param {string} path
 */
function extractNameFromPath(path) {
  return path && path !== 'root' ? path.split('/').slice(-2, -1)[0] : 'Root';
}

/**
 * @param {*} module
 * @return {import('@vue/devtools-api').CustomInspectorNode}
 */
function formatStoreForInspectorTree(module, path) {
  return {
    id: path || 'root',
    // all modules end with a `/`, we want the last segment only
    // cart/ -> cart
    // nested/cart/ -> cart
    label: extractNameFromPath(path),
    tags: module.namespaced ? [TAG_NAMESPACED] : [],
    children: Object.keys(module._children).map(function (moduleName) {
      return formatStoreForInspectorTree(module._children[moduleName], path + moduleName + '/');
    })
  };
}

/**
 * @param {import('@vue/devtools-api').CustomInspectorNode[]} result
 * @param {*} module
 * @param {string} filter
 * @param {string} path
 */
function flattenStoreForInspectorTree(result, module, filter, path) {
  if (path.includes(filter)) {
    result.push({
      id: path || 'root',
      label: path.endsWith('/') ? path.slice(0, path.length - 1) : path || 'Root',
      tags: module.namespaced ? [TAG_NAMESPACED] : []
    });
  }
  Object.keys(module._children).forEach(function (moduleName) {
    flattenStoreForInspectorTree(result, module._children[moduleName], filter, path + moduleName + '/');
  });
}

/**
 * @param {*} module
 * @return {import('@vue/devtools-api').CustomInspectorState}
 */
function formatStoreForInspectorState(module, getters, path) {
  getters = path === 'root' ? getters : getters[path];
  var gettersKeys = Object.keys(getters);
  var storeState = {
    state: Object.keys(module.state).map(function (key) {
      return {
        key: key,
        editable: true,
        value: module.state[key]
      };
    })
  };
  if (gettersKeys.length) {
    var tree = transformPathsToObjectTree(getters);
    storeState.getters = Object.keys(tree).map(function (key) {
      return {
        key: key.endsWith('/') ? extractNameFromPath(key) : key,
        editable: false,
        value: canThrow(function () {
          return tree[key];
        })
      };
    });
  }
  return storeState;
}
function transformPathsToObjectTree(getters) {
  var result = {};
  Object.keys(getters).forEach(function (key) {
    var path = key.split('/');
    if (path.length > 1) {
      var target = result;
      var leafKey = path.pop();
      path.forEach(function (p) {
        if (!target[p]) {
          target[p] = {
            _custom: {
              value: {},
              display: p,
              tooltip: 'Module',
              abstract: true
            }
          };
        }
        target = target[p]._custom.value;
      });
      target[leafKey] = canThrow(function () {
        return getters[key];
      });
    } else {
      result[key] = canThrow(function () {
        return getters[key];
      });
    }
  });
  return result;
}
function getStoreModule(moduleMap, path) {
  var names = path.split('/').filter(function (n) {
    return n;
  });
  return names.reduce(function (module, moduleName, i) {
    var child = module[moduleName];
    if (!child) {
      throw new Error("Missing module \"" + moduleName + "\" for path \"" + path + "\".");
    }
    return i === names.length - 1 ? child : child._children;
  }, path === 'root' ? moduleMap : moduleMap.root._children);
}
function canThrow(cb) {
  try {
    return cb();
  } catch (e) {
    return e;
  }
}

// Base data struct for store's module, package with some attribute and method
var Module = function Module(rawModule, runtime) {
  this.runtime = runtime;
  // Store some children item
  this._children = Object.create(null);
  // Store the origin module object which passed by programmer
  this._rawModule = rawModule;
  var rawState = rawModule.state;

  // Store the origin module's state
  this.state = (typeof rawState === 'function' ? rawState() : rawState) || {};
};
var prototypeAccessors$1 = {
  namespaced: {
    configurable: true
  }
};
prototypeAccessors$1.namespaced.get = function () {
  return !!this._rawModule.namespaced;
};
Module.prototype.addChild = function addChild(key, module) {
  this._children[key] = module;
};
Module.prototype.removeChild = function removeChild(key) {
  delete this._children[key];
};
Module.prototype.getChild = function getChild(key) {
  return this._children[key];
};
Module.prototype.hasChild = function hasChild(key) {
  return key in this._children;
};
Module.prototype.update = function update(rawModule) {
  this._rawModule.namespaced = rawModule.namespaced;
  if (rawModule.actions) {
    this._rawModule.actions = rawModule.actions;
  }
  if (rawModule.mutations) {
    this._rawModule.mutations = rawModule.mutations;
  }
  if (rawModule.getters) {
    this._rawModule.getters = rawModule.getters;
  }
};
Module.prototype.forEachChild = function forEachChild(fn) {
  forEachValue(this._children, fn);
};
Module.prototype.forEachGetter = function forEachGetter(fn) {
  if (this._rawModule.getters) {
    forEachValue(this._rawModule.getters, fn);
  }
};
Module.prototype.forEachAction = function forEachAction(fn) {
  if (this._rawModule.actions) {
    forEachValue(this._rawModule.actions, fn);
  }
};
Module.prototype.forEachMutation = function forEachMutation(fn) {
  if (this._rawModule.mutations) {
    forEachValue(this._rawModule.mutations, fn);
  }
};
Object.defineProperties(Module.prototype, prototypeAccessors$1);
var ModuleCollection = function ModuleCollection(rawRootModule) {
  // register root module (Vuex.Store options)
  this.register([], rawRootModule, false);
};
ModuleCollection.prototype.get = function get(path) {
  return path.reduce(function (module, key) {
    return module.getChild(key);
  }, this.root);
};
ModuleCollection.prototype.getNamespace = function getNamespace(path) {
  var module = this.root;
  return path.reduce(function (namespace, key) {
    module = module.getChild(key);
    return namespace + (module.namespaced ? key + '/' : '');
  }, '');
};
ModuleCollection.prototype.update = function update$1(rawRootModule) {
  update([], this.root, rawRootModule);
};
ModuleCollection.prototype.register = function register(path, rawModule, runtime) {
  var this$1$1 = this;
  if (runtime === void 0) runtime = true;
  var newModule = new Module(rawModule, runtime);
  if (path.length === 0) {
    this.root = newModule;
  } else {
    var parent = this.get(path.slice(0, -1));
    parent.addChild(path[path.length - 1], newModule);
  }

  // register nested modules
  if (rawModule.modules) {
    forEachValue(rawModule.modules, function (rawChildModule, key) {
      this$1$1.register(path.concat(key), rawChildModule, runtime);
    });
  }
};
ModuleCollection.prototype.unregister = function unregister(path) {
  var parent = this.get(path.slice(0, -1));
  var key = path[path.length - 1];
  var child = parent.getChild(key);
  if (!child) {
    return;
  }
  if (!child.runtime) {
    return;
  }
  parent.removeChild(key);
};
ModuleCollection.prototype.isRegistered = function isRegistered(path) {
  var parent = this.get(path.slice(0, -1));
  var key = path[path.length - 1];
  if (parent) {
    return parent.hasChild(key);
  }
  return false;
};
function update(path, targetModule, newModule) {

  // update target module
  targetModule.update(newModule);

  // update nested modules
  if (newModule.modules) {
    for (var key in newModule.modules) {
      if (!targetModule.getChild(key)) {
        return;
      }
      update(path.concat(key), targetModule.getChild(key), newModule.modules[key]);
    }
  }
}
function createStore(options) {
  return new Store(options);
}
var Store = function Store(options) {
  var this$1$1 = this;
  if (options === void 0) options = {};
  var plugins = options.plugins;
  if (plugins === void 0) plugins = [];
  var strict = options.strict;
  if (strict === void 0) strict = false;
  var devtools = options.devtools;

  // store internal state
  this._committing = false;
  this._actions = Object.create(null);
  this._actionSubscribers = [];
  this._mutations = Object.create(null);
  this._wrappedGetters = Object.create(null);
  this._modules = new ModuleCollection(options);
  this._modulesNamespaceMap = Object.create(null);
  this._subscribers = [];
  this._makeLocalGettersCache = Object.create(null);

  // EffectScope instance. when registering new getters, we wrap them inside
  // EffectScope so that getters (computed) would not be destroyed on
  // component unmount.
  this._scope = null;
  this._devtools = devtools;

  // bind commit and dispatch to self
  var store = this;
  var ref = this;
  var dispatch = ref.dispatch;
  var commit = ref.commit;
  this.dispatch = function boundDispatch(type, payload) {
    return dispatch.call(store, type, payload);
  };
  this.commit = function boundCommit(type, payload, options) {
    return commit.call(store, type, payload, options);
  };

  // strict mode
  this.strict = strict;
  var state = this._modules.root.state;

  // init root module.
  // this also recursively registers all sub-modules
  // and collects all module getters inside this._wrappedGetters
  installModule(this, state, [], this._modules.root);

  // initialize the store state, which is responsible for the reactivity
  // (also registers _wrappedGetters as computed properties)
  resetStoreState(this, state);

  // apply plugins
  plugins.forEach(function (plugin) {
    return plugin(this$1$1);
  });
};
var prototypeAccessors = {
  state: {
    configurable: true
  }
};
Store.prototype.install = function install(app, injectKey) {
  app.provide(injectKey || storeKey, this);
  app.config.globalProperties.$store = this;
  var useDevtools = this._devtools !== undefined ? this._devtools : false;
  if (useDevtools) {
    addDevtools(app, this);
  }
};
prototypeAccessors.state.get = function () {
  return this._state.data;
};
prototypeAccessors.state.set = function (v) {
};
Store.prototype.commit = function commit(_type, _payload, _options) {
  var this$1$1 = this;

  // check object-style commit
  var ref = unifyObjectStyle(_type, _payload, _options);
  var type = ref.type;
  var payload = ref.payload;
  var mutation = {
    type: type,
    payload: payload
  };
  var entry = this._mutations[type];
  if (!entry) {
    return;
  }
  this._withCommit(function () {
    entry.forEach(function commitIterator(handler) {
      handler(payload);
    });
  });
  this._subscribers.slice() // shallow copy to prevent iterator invalidation if subscriber synchronously calls unsubscribe
  .forEach(function (sub) {
    return sub(mutation, this$1$1.state);
  });
};
Store.prototype.dispatch = function dispatch(_type, _payload) {
  var this$1$1 = this;

  // check object-style dispatch
  var ref = unifyObjectStyle(_type, _payload);
  var type = ref.type;
  var payload = ref.payload;
  var action = {
    type: type,
    payload: payload
  };
  var entry = this._actions[type];
  if (!entry) {
    return;
  }
  try {
    this._actionSubscribers.slice() // shallow copy to prevent iterator invalidation if subscriber synchronously calls unsubscribe
    .filter(function (sub) {
      return sub.before;
    }).forEach(function (sub) {
      return sub.before(action, this$1$1.state);
    });
  } catch (e) {
  }
  var result = entry.length > 1 ? Promise.all(entry.map(function (handler) {
    return handler(payload);
  })) : entry[0](payload);
  return new Promise(function (resolve, reject) {
    result.then(function (res) {
      try {
        this$1$1._actionSubscribers.filter(function (sub) {
          return sub.after;
        }).forEach(function (sub) {
          return sub.after(action, this$1$1.state);
        });
      } catch (e) {
      }
      resolve(res);
    }, function (error) {
      try {
        this$1$1._actionSubscribers.filter(function (sub) {
          return sub.error;
        }).forEach(function (sub) {
          return sub.error(action, this$1$1.state, error);
        });
      } catch (e) {
      }
      reject(error);
    });
  });
};
Store.prototype.subscribe = function subscribe(fn, options) {
  return genericSubscribe(fn, this._subscribers, options);
};
Store.prototype.subscribeAction = function subscribeAction(fn, options) {
  var subs = typeof fn === 'function' ? {
    before: fn
  } : fn;
  return genericSubscribe(subs, this._actionSubscribers, options);
};
Store.prototype.watch = function watch$1(getter, cb, options) {
  var this$1$1 = this;
  return watch(function () {
    return getter(this$1$1.state, this$1$1.getters);
  }, cb, Object.assign({}, options));
};
Store.prototype.replaceState = function replaceState(state) {
  var this$1$1 = this;
  this._withCommit(function () {
    this$1$1._state.data = state;
  });
};
Store.prototype.registerModule = function registerModule(path, rawModule, options) {
  if (options === void 0) options = {};
  if (typeof path === 'string') {
    path = [path];
  }
  this._modules.register(path, rawModule);
  installModule(this, this.state, path, this._modules.get(path), options.preserveState);
  // reset store to update getters...
  resetStoreState(this, this.state);
};
Store.prototype.unregisterModule = function unregisterModule(path) {
  var this$1$1 = this;
  if (typeof path === 'string') {
    path = [path];
  }
  this._modules.unregister(path);
  this._withCommit(function () {
    var parentState = getNestedState(this$1$1.state, path.slice(0, -1));
    delete parentState[path[path.length - 1]];
  });
  resetStore(this);
};
Store.prototype.hasModule = function hasModule(path) {
  if (typeof path === 'string') {
    path = [path];
  }
  return this._modules.isRegistered(path);
};
Store.prototype.hotUpdate = function hotUpdate(newOptions) {
  this._modules.update(newOptions);
  resetStore(this, true);
};
Store.prototype._withCommit = function _withCommit(fn) {
  var committing = this._committing;
  this._committing = true;
  fn();
  this._committing = committing;
};
Object.defineProperties(Store.prototype, prototypeAccessors);

var script$6 = {
  name: 'WorkflowTitlebar',
  props: {
    saveStatus: {
      type: String,
      default: 'upToDate',
    },
  },
  computed: {
    workflow() {
      return this.$store.getters.workflow || { title: 'COM_WORKFLOW_GRAPH_LOADING', published: false };
    },
    stagesCount() {
      return this.$store.getters.stages?.length || 0;
    },
    transitionsCount() {
      return this.$store.getters.transitions?.length || 0;
    },
  },
};

const _hoisted_1$b = {
  class: "d-flex flex-wrap align-items-center justify-content-between",
  "aria-labelledby": "workflow-main-title"
};
const _hoisted_2$9 = { class: "col-md-6 d-flex flex-column" };
const _hoisted_3$9 = {
  id: "workflow-main-title",
  class: "mb-2"
};
const _hoisted_4$6 = {
  class: "d-flex align-items-center flex-wrap mb-0",
  "aria-label": "Workflow Details"
};
const _hoisted_5$5 = { class: "visually-hidden" };
const _hoisted_6$4 = { class: "me-3 mb-1 d-flex mb-0" };
const _hoisted_7$4 = ["aria-label"];
const _hoisted_8$4 = { class: "visually-hidden" };
const _hoisted_9$4 = { class: "me-3 mb-1 d-flex mb-0" };
const _hoisted_10$3 = { class: "visually-hidden" };
const _hoisted_11$3 = { class: "me-3 mb-1 d-flex mb-0" };
const _hoisted_12$3 = ["aria-label"];

function render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return (openBlock(), createElementBlock("section", _hoisted_1$b, [
    createBaseVNode("div", _hoisted_2$9, [
      createBaseVNode("h1", _hoisted_3$9, toDisplayString(_ctx.translate($options.workflow?.title)), 1 /* TEXT */),
      createBaseVNode("dl", _hoisted_4$6, [
        createBaseVNode("dt", _hoisted_5$5, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_STATUS')), 1 /* TEXT */),
        createBaseVNode("dd", _hoisted_6$4, [
          createBaseVNode("span", {
            class: normalizeClass(["badge", $options.workflow.published ? 'bg-success' : 'bg-warning']),
            role: "status",
            "aria-label": `${$options.workflow.published ? _ctx.translate('COM_WORKFLOW_GRAPH_ENABLED') : _ctx.translate('COM_WORKFLOW_GRAPH_DISABLED')}`
          }, toDisplayString($options.workflow.published ? _ctx.translate('COM_WORKFLOW_GRAPH_ENABLED') : _ctx.translate('COM_WORKFLOW_GRAPH_DISABLED')), 11 /* TEXT, CLASS, PROPS */, _hoisted_7$4)
        ]),
        createBaseVNode("dt", _hoisted_8$4, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_STAGE_COUNT')), 1 /* TEXT */),
        createBaseVNode("dd", _hoisted_9$4, [
          createBaseVNode("span", null, toDisplayString($options.stagesCount) + " " + toDisplayString($options.stagesCount === 1 ? _ctx.translate('COM_WORKFLOW_GRAPH_STAGE') : _ctx.translate('COM_WORKFLOW_GRAPH_STAGES')), 1 /* TEXT */)
        ]),
        createBaseVNode("dt", _hoisted_10$3, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_TRANSITION_COUNT')), 1 /* TEXT */),
        createBaseVNode("dd", _hoisted_11$3, [
          createBaseVNode("span", null, toDisplayString($options.transitionsCount) + " " + toDisplayString($options.transitionsCount === 1 ? _ctx.translate('COM_WORKFLOW_GRAPH_TRANSITION')
              : _ctx.translate('COM_WORKFLOW_GRAPH_TRANSITIONS')), 1 /* TEXT */)
        ])
      ])
    ]),
    createBaseVNode("div", {
      id: "save-status",
      class: normalizeClass(["mb-2 fw-bold", {
        'text-warning': $props.saveStatus?.value === 'unsaved',
      }]),
      role: "status",
      "aria-live": "polite",
      "aria-label": _ctx.translate($props.saveStatus?.value === 'unsaved' ? 'COM_WORKFLOW_GRAPH_UNSAVED_CHANGES' : 'COM_WORKFLOW_GRAPH_UP_TO_DATE')
    }, toDisplayString($props.saveStatus.value === 'unsaved'
          ? _ctx.translate('COM_WORKFLOW_GRAPH_UNSAVED_CHANGES')
          : _ctx.translate('COM_WORKFLOW_GRAPH_UP_TO_DATE')), 11 /* TEXT, CLASS, PROPS */, _hoisted_12$3)
  ]))
}

script$6.render = render$5;
script$6.__file = "media_source/com_workflow/src/components/Titlebar.vue";

function _arrayLikeToArray(r, a) {
  (null == a || a > r.length) && (a = r.length);
  for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e];
  return n;
}
function _extends() {
  return _extends = Object.assign ? Object.assign.bind() : function (n) {
    for (var e = 1; e < arguments.length; e++) {
      var t = arguments[e];
      for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]);
    }
    return n;
  }, _extends.apply(null, arguments);
}
function _objectWithoutPropertiesLoose(r, e) {
  if (null == r) return {};
  var t = {};
  for (var n in r) if ({}.hasOwnProperty.call(r, n)) {
    if (-1 !== e.indexOf(n)) continue;
    t[n] = r[n];
  }
  return t;
}

const _excluded = ["eventFilter"],
  _excluded2 = ["eventFilter"],
  _excluded3 = ["id"],
  _excluded4 = ["computedPosition", "handleBounds", "selected", "dimensions", "isParent", "resizing", "dragging", "events"],
  _excluded5 = ["selected", "sourceNode", "targetNode", "events"];
function tryOnScopeDispose(fn) {
  if (getCurrentScope()) {
    onScopeDispose(fn);
    return true;
  }
  return false;
}
function toValue(r) {
  return typeof r === "function" ? r() : unref(r);
}
const isClient = typeof window !== "undefined" && typeof document !== "undefined";
const isDef$1 = val => typeof val !== "undefined";
const toString = Object.prototype.toString;
const isObject = val => toString.call(val) === "[object Object]";
const noop$3 = () => {};
function createFilterWrapper(filter2, fn) {
  function wrapper() {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    return new Promise((resolve, reject) => {
      Promise.resolve(filter2(() => fn.apply(this, args), {
        fn,
        thisArg: this,
        args
      })).then(resolve).catch(reject);
    });
  }
  return wrapper;
}
const bypassFilter = invoke => {
  return invoke();
};
function pausableFilter(extendFilter) {
  if (extendFilter === void 0) {
    extendFilter = bypassFilter;
  }
  const isActive = ref(true);
  function pause() {
    isActive.value = false;
  }
  function resume() {
    isActive.value = true;
  }
  const eventFilter = function eventFilter() {
    if (isActive.value) extendFilter(...arguments);
  };
  return {
    isActive: readonly(isActive),
    pause,
    resume,
    eventFilter
  };
}
function promiseTimeout(ms, throwOnTimeout, reason) {
  if (throwOnTimeout === void 0) {
    throwOnTimeout = false;
  }
  if (reason === void 0) {
    reason = "Timeout";
  }
  return new Promise((resolve, reject) => {
    if (throwOnTimeout) setTimeout(() => reject(reason), ms);else setTimeout(resolve, ms);
  });
}
function watchWithFilter(source, cb, options) {
  if (options === void 0) {
    options = {};
  }
  const _options = options,
    _options$eventFilter = _options.eventFilter,
    eventFilter = _options$eventFilter === void 0 ? bypassFilter : _options$eventFilter,
    watchOptions = _objectWithoutPropertiesLoose(_options, _excluded);
  return watch(source, createFilterWrapper(eventFilter, cb), watchOptions);
}
function watchPausable(source, cb, options) {
  if (options === void 0) {
    options = {};
  }
  const _options2 = options,
    filter2 = _options2.eventFilter,
    watchOptions = _objectWithoutPropertiesLoose(_options2, _excluded2);
  const _pausableFilter = pausableFilter(filter2),
    eventFilter = _pausableFilter.eventFilter,
    pause = _pausableFilter.pause,
    resume = _pausableFilter.resume,
    isActive = _pausableFilter.isActive;
  const stop = watchWithFilter(source, cb, _extends({}, watchOptions, {
    eventFilter
  }));
  return {
    stop,
    pause,
    resume,
    isActive
  };
}
function toRefs(objectRef, options) {
  if (options === void 0) {
    options = {};
  }
  if (!isRef(objectRef)) return toRefs$1(objectRef);
  const result = Array.isArray(objectRef.value) ? Array.from({
    length: objectRef.value.length
  }) : {};
  for (const key in objectRef.value) {
    result[key] = customRef(() => ({
      get() {
        return objectRef.value[key];
      },
      set(v) {
        var _a;
        const replaceRef = (_a = toValue(options.replaceRef)) != null ? _a : true;
        if (replaceRef) {
          if (Array.isArray(objectRef.value)) {
            const copy = [...objectRef.value];
            copy[key] = v;
            objectRef.value = copy;
          } else {
            const newObject = _extends({}, objectRef.value, {
              [key]: v
            });
            Object.setPrototypeOf(newObject, Object.getPrototypeOf(objectRef.value));
            objectRef.value = newObject;
          }
        } else {
          objectRef.value[key] = v;
        }
      }
    }));
  }
  return result;
}
function createUntil(r, isNot) {
  if (isNot === void 0) {
    isNot = false;
  }
  function toMatch(condition, _temp) {
    let _ref = _temp === void 0 ? {} : _temp,
      _ref$flush = _ref.flush,
      flush = _ref$flush === void 0 ? "sync" : _ref$flush,
      _ref$deep = _ref.deep,
      deep = _ref$deep === void 0 ? false : _ref$deep,
      timeout2 = _ref.timeout,
      throwOnTimeout = _ref.throwOnTimeout;
    let stop = null;
    const watcher = new Promise(resolve => {
      stop = watch(r, v => {
        if (condition(v) !== isNot) {
          stop == null ? void 0 : stop();
          resolve(v);
        }
      }, {
        flush,
        deep,
        immediate: true
      });
    });
    const promises = [watcher];
    if (timeout2 != null) {
      promises.push(promiseTimeout(timeout2, throwOnTimeout).then(() => toValue(r)).finally(() => stop == null ? void 0 : stop()));
    }
    return Promise.race(promises);
  }
  function toBe(value, options) {
    if (!isRef(value)) return toMatch(v => v === value, options);
    const _ref2 = options != null ? options : {},
      _ref2$flush = _ref2.flush,
      flush = _ref2$flush === void 0 ? "sync" : _ref2$flush,
      _ref2$deep = _ref2.deep,
      deep = _ref2$deep === void 0 ? false : _ref2$deep,
      timeout2 = _ref2.timeout,
      throwOnTimeout = _ref2.throwOnTimeout;
    let stop = null;
    const watcher = new Promise(resolve => {
      stop = watch([r, value], _ref3 => {
        let v1 = _ref3[0],
          v2 = _ref3[1];
        if (isNot !== (v1 === v2)) {
          stop == null ? void 0 : stop();
          resolve(v1);
        }
      }, {
        flush,
        deep,
        immediate: true
      });
    });
    const promises = [watcher];
    if (timeout2 != null) {
      promises.push(promiseTimeout(timeout2, throwOnTimeout).then(() => toValue(r)).finally(() => {
        stop == null ? void 0 : stop();
        return toValue(r);
      }));
    }
    return Promise.race(promises);
  }
  function toBeTruthy(options) {
    return toMatch(v => Boolean(v), options);
  }
  function toBeNull(options) {
    return toBe(null, options);
  }
  function toBeUndefined(options) {
    return toBe(void 0, options);
  }
  function toBeNaN(options) {
    return toMatch(Number.isNaN, options);
  }
  function toContains(value, options) {
    return toMatch(v => {
      const array2 = Array.from(v);
      return array2.includes(value) || array2.includes(toValue(value));
    }, options);
  }
  function changed(options) {
    return changedTimes(1, options);
  }
  function changedTimes(n, options) {
    if (n === void 0) {
      n = 1;
    }
    let count = -1;
    return toMatch(() => {
      count += 1;
      return count >= n;
    }, options);
  }
  if (Array.isArray(toValue(r))) {
    const instance = {
      toMatch,
      toContains,
      changed,
      changedTimes,
      get not() {
        return createUntil(r, !isNot);
      }
    };
    return instance;
  } else {
    const instance = {
      toMatch,
      toBe,
      toBeTruthy,
      toBeNull,
      toBeNaN,
      toBeUndefined,
      changed,
      changedTimes,
      get not() {
        return createUntil(r, !isNot);
      }
    };
    return instance;
  }
}
function until(r) {
  return createUntil(r);
}
function unrefElement(elRef) {
  var _a;
  const plain = toValue(elRef);
  return (_a = plain == null ? void 0 : plain.$el) != null ? _a : plain;
}
const defaultWindow = isClient ? window : void 0;
function useEventListener() {
  let target;
  let events;
  let listeners;
  let options;
  for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
    args[_key2] = arguments[_key2];
  }
  if (typeof args[0] === "string" || Array.isArray(args[0])) {
    events = args[0];
    listeners = args[1];
    options = args[2];
    target = defaultWindow;
  } else {
    target = args[0];
    events = args[1];
    listeners = args[2];
    options = args[3];
  }
  if (!target) return noop$3;
  if (!Array.isArray(events)) events = [events];
  if (!Array.isArray(listeners)) listeners = [listeners];
  const cleanups = [];
  const cleanup = () => {
    cleanups.forEach(fn => fn());
    cleanups.length = 0;
  };
  const register = (el, event, listener, options2) => {
    el.addEventListener(event, listener, options2);
    return () => el.removeEventListener(event, listener, options2);
  };
  const stopWatch = watch(() => [unrefElement(target), toValue(options)], _ref4 => {
    let el = _ref4[0],
      options2 = _ref4[1];
    cleanup();
    if (!el) return;
    const optionsClone = isObject(options2) ? _extends({}, options2) : options2;
    cleanups.push(...events.flatMap(event => {
      return listeners.map(listener => register(el, event, listener, optionsClone));
    }));
  }, {
    immediate: true,
    flush: "post"
  });
  const stop = () => {
    stopWatch();
    cleanup();
  };
  tryOnScopeDispose(stop);
  return stop;
}
function createKeyPredicate$1(keyFilter) {
  if (typeof keyFilter === "function") return keyFilter;else if (typeof keyFilter === "string") return event => event.key === keyFilter;else if (Array.isArray(keyFilter)) return event => keyFilter.includes(event.key);
  return () => true;
}
function onKeyStroke() {
  let key;
  let handler;
  let options = {};
  if (arguments.length === 3) {
    key = arguments.length <= 0 ? undefined : arguments[0];
    handler = arguments.length <= 1 ? undefined : arguments[1];
    options = arguments.length <= 2 ? undefined : arguments[2];
  } else if (arguments.length === 2) {
    if (typeof (arguments.length <= 1 ? undefined : arguments[1]) === "object") {
      key = true;
      handler = arguments.length <= 0 ? undefined : arguments[0];
      options = arguments.length <= 1 ? undefined : arguments[1];
    } else {
      key = arguments.length <= 0 ? undefined : arguments[0];
      handler = arguments.length <= 1 ? undefined : arguments[1];
    }
  } else {
    key = true;
    handler = arguments.length <= 0 ? undefined : arguments[0];
  }
  const _options3 = options,
    _options3$target = _options3.target,
    target = _options3$target === void 0 ? defaultWindow : _options3$target,
    _options3$eventName = _options3.eventName,
    eventName = _options3$eventName === void 0 ? "keydown" : _options3$eventName,
    _options3$passive = _options3.passive,
    passive = _options3$passive === void 0 ? false : _options3$passive,
    _options3$dedupe = _options3.dedupe,
    dedupe = _options3$dedupe === void 0 ? false : _options3$dedupe;
  const predicate = createKeyPredicate$1(key);
  const listener = e => {
    if (e.repeat && toValue(dedupe)) return;
    if (predicate(e)) handler(e);
  };
  return useEventListener(target, eventName, listener, passive);
}
function cloneFnJSON(source) {
  return JSON.parse(JSON.stringify(source));
}
function useVModel(props, key, emit, options) {
  if (options === void 0) {
    options = {};
  }
  var _a, _b, _c;
  const _options4 = options,
    _options4$clone = _options4.clone,
    clone = _options4$clone === void 0 ? false : _options4$clone,
    _options4$passive = _options4.passive,
    passive = _options4$passive === void 0 ? false : _options4$passive,
    eventName = _options4.eventName,
    _options4$deep = _options4.deep,
    deep = _options4$deep === void 0 ? false : _options4$deep,
    defaultValue = _options4.defaultValue,
    shouldEmit = _options4.shouldEmit;
  const vm = getCurrentInstance();
  const _emit = emit || (vm == null ? void 0 : vm.emit) || ((_a = vm == null ? void 0 : vm.$emit) == null ? void 0 : _a.bind(vm)) || ((_c = (_b = vm == null ? void 0 : vm.proxy) == null ? void 0 : _b.$emit) == null ? void 0 : _c.bind(vm == null ? void 0 : vm.proxy));
  let event = eventName;
  if (!key) {
    {
      key = "modelValue";
    }
  }
  event = event || "update:" + key.toString();
  const cloneFn = val => !clone ? val : typeof clone === "function" ? clone(val) : cloneFnJSON(val);
  const getValue = () => isDef$1(props[key]) ? cloneFn(props[key]) : defaultValue;
  const triggerEmit = value => {
    if (shouldEmit) {
      if (shouldEmit(value)) _emit(event, value);
    } else {
      _emit(event, value);
    }
  };
  if (passive) {
    const initialValue = getValue();
    const proxy = ref(initialValue);
    let isUpdating = false;
    watch(() => props[key], v => {
      if (!isUpdating) {
        isUpdating = true;
        proxy.value = cloneFn(v);
        nextTick(() => isUpdating = false);
      }
    });
    watch(proxy, v => {
      if (!isUpdating && (v !== props[key] || deep)) triggerEmit(v);
    }, {
      deep
    });
    return proxy;
  } else {
    return computed({
      get() {
        return getValue();
      },
      set(value) {
        triggerEmit(value);
      }
    });
  }
}
var noop$2 = {
  value: () => {}
};
function dispatch$1() {
  for (var i = 0, n = arguments.length, _ = {}, t; i < n; ++i) {
    if (!(t = arguments[i] + "") || t in _ || /[\s.]/.test(t)) throw new Error("illegal type: " + t);
    _[t] = [];
  }
  return new Dispatch$1(_);
}
function Dispatch$1(_) {
  this._ = _;
}
function parseTypenames$1$1(typenames, types) {
  return typenames.trim().split(/^|\s+/).map(function (t) {
    var name = "",
      i = t.indexOf(".");
    if (i >= 0) name = t.slice(i + 1), t = t.slice(0, i);
    if (t && !types.hasOwnProperty(t)) throw new Error("unknown type: " + t);
    return {
      type: t,
      name
    };
  });
}
Dispatch$1.prototype = dispatch$1.prototype = {
  constructor: Dispatch$1,
  on: function on(typename, callback) {
    var _ = this._,
      T = parseTypenames$1$1(typename + "", _),
      t,
      i = -1,
      n = T.length;
    if (arguments.length < 2) {
      while (++i < n) if ((t = (typename = T[i]).type) && (t = get$1$1(_[t], typename.name))) return t;
      return;
    }
    if (callback != null && typeof callback !== "function") throw new Error("invalid callback: " + callback);
    while (++i < n) {
      if (t = (typename = T[i]).type) _[t] = set$1$1(_[t], typename.name, callback);else if (callback == null) for (t in _) _[t] = set$1$1(_[t], typename.name, null);
    }
    return this;
  },
  copy: function copy() {
    var copy = {},
      _ = this._;
    for (var t in _) copy[t] = _[t].slice();
    return new Dispatch$1(copy);
  },
  call: function call(type, that) {
    if ((n = arguments.length - 2) > 0) for (var args = new Array(n), i = 0, n, t; i < n; ++i) args[i] = arguments[i + 2];
    if (!this._.hasOwnProperty(type)) throw new Error("unknown type: " + type);
    for (t = this._[type], i = 0, n = t.length; i < n; ++i) t[i].value.apply(that, args);
  },
  apply: function apply(type, that, args) {
    if (!this._.hasOwnProperty(type)) throw new Error("unknown type: " + type);
    for (var t = this._[type], i = 0, n = t.length; i < n; ++i) t[i].value.apply(that, args);
  }
};
function get$1$1(type, name) {
  for (var i = 0, n = type.length, c; i < n; ++i) {
    if ((c = type[i]).name === name) {
      return c.value;
    }
  }
}
function set$1$1(type, name, callback) {
  for (var i = 0, n = type.length; i < n; ++i) {
    if (type[i].name === name) {
      type[i] = noop$2, type = type.slice(0, i).concat(type.slice(i + 1));
      break;
    }
  }
  if (callback != null) type.push({
    name,
    value: callback
  });
  return type;
}
var xhtml$1 = "http://www.w3.org/1999/xhtml";
const namespaces$1 = {
  svg: "http://www.w3.org/2000/svg",
  xhtml: xhtml$1,
  xlink: "http://www.w3.org/1999/xlink",
  xml: "http://www.w3.org/XML/1998/namespace",
  xmlns: "http://www.w3.org/2000/xmlns/"
};
function namespace$1(name) {
  var prefix = name += "",
    i = prefix.indexOf(":");
  if (i >= 0 && (prefix = name.slice(0, i)) !== "xmlns") name = name.slice(i + 1);
  return namespaces$1.hasOwnProperty(prefix) ? {
    space: namespaces$1[prefix],
    local: name
  } : name;
}
function creatorInherit$1(name) {
  return function () {
    var document2 = this.ownerDocument,
      uri = this.namespaceURI;
    return uri === xhtml$1 && document2.documentElement.namespaceURI === xhtml$1 ? document2.createElement(name) : document2.createElementNS(uri, name);
  };
}
function creatorFixed$1(fullname) {
  return function () {
    return this.ownerDocument.createElementNS(fullname.space, fullname.local);
  };
}
function creator$1(name) {
  var fullname = namespace$1(name);
  return (fullname.local ? creatorFixed$1 : creatorInherit$1)(fullname);
}
function none$1() {}
function selector$1(selector2) {
  return selector2 == null ? none$1 : function () {
    return this.querySelector(selector2);
  };
}
function selection_select$1(select2) {
  if (typeof select2 !== "function") select2 = selector$1(select2);
  for (var groups = this._groups, m = groups.length, subgroups = new Array(m), j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, subgroup = subgroups[j] = new Array(n), node, subnode, i = 0; i < n; ++i) {
      if ((node = group[i]) && (subnode = select2.call(node, node.__data__, i, group))) {
        if ("__data__" in node) subnode.__data__ = node.__data__;
        subgroup[i] = subnode;
      }
    }
  }
  return new Selection$1$1(subgroups, this._parents);
}
function array$1(x) {
  return x == null ? [] : Array.isArray(x) ? x : Array.from(x);
}
function empty$1() {
  return [];
}
function selectorAll$1(selector2) {
  return selector2 == null ? empty$1 : function () {
    return this.querySelectorAll(selector2);
  };
}
function arrayAll$1(select2) {
  return function () {
    return array$1(select2.apply(this, arguments));
  };
}
function selection_selectAll$1(select2) {
  if (typeof select2 === "function") select2 = arrayAll$1(select2);else select2 = selectorAll$1(select2);
  for (var groups = this._groups, m = groups.length, subgroups = [], parents = [], j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, node, i = 0; i < n; ++i) {
      if (node = group[i]) {
        subgroups.push(select2.call(node, node.__data__, i, group));
        parents.push(node);
      }
    }
  }
  return new Selection$1$1(subgroups, parents);
}
function matcher$1(selector2) {
  return function () {
    return this.matches(selector2);
  };
}
function childMatcher$1(selector2) {
  return function (node) {
    return node.matches(selector2);
  };
}
var find$1 = Array.prototype.find;
function childFind$1(match) {
  return function () {
    return find$1.call(this.children, match);
  };
}
function childFirst$1() {
  return this.firstElementChild;
}
function selection_selectChild$1(match) {
  return this.select(match == null ? childFirst$1 : childFind$1(typeof match === "function" ? match : childMatcher$1(match)));
}
var filter$1 = Array.prototype.filter;
function children$1() {
  return Array.from(this.children);
}
function childrenFilter$1(match) {
  return function () {
    return filter$1.call(this.children, match);
  };
}
function selection_selectChildren$1(match) {
  return this.selectAll(match == null ? children$1 : childrenFilter$1(typeof match === "function" ? match : childMatcher$1(match)));
}
function selection_filter$1(match) {
  if (typeof match !== "function") match = matcher$1(match);
  for (var groups = this._groups, m = groups.length, subgroups = new Array(m), j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, subgroup = subgroups[j] = [], node, i = 0; i < n; ++i) {
      if ((node = group[i]) && match.call(node, node.__data__, i, group)) {
        subgroup.push(node);
      }
    }
  }
  return new Selection$1$1(subgroups, this._parents);
}
function sparse$1(update) {
  return new Array(update.length);
}
function selection_enter$1() {
  return new Selection$1$1(this._enter || this._groups.map(sparse$1), this._parents);
}
function EnterNode$1(parent, datum2) {
  this.ownerDocument = parent.ownerDocument;
  this.namespaceURI = parent.namespaceURI;
  this._next = null;
  this._parent = parent;
  this.__data__ = datum2;
}
EnterNode$1.prototype = {
  constructor: EnterNode$1,
  appendChild: function appendChild(child) {
    return this._parent.insertBefore(child, this._next);
  },
  insertBefore: function insertBefore(child, next) {
    return this._parent.insertBefore(child, next);
  },
  querySelector: function querySelector(selector2) {
    return this._parent.querySelector(selector2);
  },
  querySelectorAll: function querySelectorAll(selector2) {
    return this._parent.querySelectorAll(selector2);
  }
};
function constant$3(x) {
  return function () {
    return x;
  };
}
function bindIndex$1(parent, group, enter, update, exit, data) {
  var i = 0,
    node,
    groupLength = group.length,
    dataLength = data.length;
  for (; i < dataLength; ++i) {
    if (node = group[i]) {
      node.__data__ = data[i];
      update[i] = node;
    } else {
      enter[i] = new EnterNode$1(parent, data[i]);
    }
  }
  for (; i < groupLength; ++i) {
    if (node = group[i]) {
      exit[i] = node;
    }
  }
}
function bindKey$1(parent, group, enter, update, exit, data, key) {
  var i,
    node,
    nodeByKeyValue = /* @__PURE__ */new Map(),
    groupLength = group.length,
    dataLength = data.length,
    keyValues = new Array(groupLength),
    keyValue;
  for (i = 0; i < groupLength; ++i) {
    if (node = group[i]) {
      keyValues[i] = keyValue = key.call(node, node.__data__, i, group) + "";
      if (nodeByKeyValue.has(keyValue)) {
        exit[i] = node;
      } else {
        nodeByKeyValue.set(keyValue, node);
      }
    }
  }
  for (i = 0; i < dataLength; ++i) {
    keyValue = key.call(parent, data[i], i, data) + "";
    if (node = nodeByKeyValue.get(keyValue)) {
      update[i] = node;
      node.__data__ = data[i];
      nodeByKeyValue.delete(keyValue);
    } else {
      enter[i] = new EnterNode$1(parent, data[i]);
    }
  }
  for (i = 0; i < groupLength; ++i) {
    if ((node = group[i]) && nodeByKeyValue.get(keyValues[i]) === node) {
      exit[i] = node;
    }
  }
}
function datum$1(node) {
  return node.__data__;
}
function selection_data$1(value, key) {
  if (!arguments.length) return Array.from(this, datum$1);
  var bind = key ? bindKey$1 : bindIndex$1,
    parents = this._parents,
    groups = this._groups;
  if (typeof value !== "function") value = constant$3(value);
  for (var m = groups.length, update = new Array(m), enter = new Array(m), exit = new Array(m), j = 0; j < m; ++j) {
    var parent = parents[j],
      group = groups[j],
      groupLength = group.length,
      data = arraylike$1(value.call(parent, parent && parent.__data__, j, parents)),
      dataLength = data.length,
      enterGroup = enter[j] = new Array(dataLength),
      updateGroup = update[j] = new Array(dataLength),
      exitGroup = exit[j] = new Array(groupLength);
    bind(parent, group, enterGroup, updateGroup, exitGroup, data, key);
    for (var i0 = 0, i1 = 0, previous, next; i0 < dataLength; ++i0) {
      if (previous = enterGroup[i0]) {
        if (i0 >= i1) i1 = i0 + 1;
        while (!(next = updateGroup[i1]) && ++i1 < dataLength);
        previous._next = next || null;
      }
    }
  }
  update = new Selection$1$1(update, parents);
  update._enter = enter;
  update._exit = exit;
  return update;
}
function arraylike$1(data) {
  return typeof data === "object" && "length" in data ? data : Array.from(data);
}
function selection_exit$1() {
  return new Selection$1$1(this._exit || this._groups.map(sparse$1), this._parents);
}
function selection_join$1(onenter, onupdate, onexit) {
  var enter = this.enter(),
    update = this,
    exit = this.exit();
  if (typeof onenter === "function") {
    enter = onenter(enter);
    if (enter) enter = enter.selection();
  } else {
    enter = enter.append(onenter + "");
  }
  if (onupdate != null) {
    update = onupdate(update);
    if (update) update = update.selection();
  }
  if (onexit == null) exit.remove();else onexit(exit);
  return enter && update ? enter.merge(update).order() : update;
}
function selection_merge$1(context) {
  var selection2 = context.selection ? context.selection() : context;
  for (var groups0 = this._groups, groups1 = selection2._groups, m0 = groups0.length, m1 = groups1.length, m = Math.min(m0, m1), merges = new Array(m0), j = 0; j < m; ++j) {
    for (var group0 = groups0[j], group1 = groups1[j], n = group0.length, merge = merges[j] = new Array(n), node, i = 0; i < n; ++i) {
      if (node = group0[i] || group1[i]) {
        merge[i] = node;
      }
    }
  }
  for (; j < m0; ++j) {
    merges[j] = groups0[j];
  }
  return new Selection$1$1(merges, this._parents);
}
function selection_order$1() {
  for (var groups = this._groups, j = -1, m = groups.length; ++j < m;) {
    for (var group = groups[j], i = group.length - 1, next = group[i], node; --i >= 0;) {
      if (node = group[i]) {
        if (next && node.compareDocumentPosition(next) ^ 4) next.parentNode.insertBefore(node, next);
        next = node;
      }
    }
  }
  return this;
}
function selection_sort$1(compare) {
  if (!compare) compare = ascending$1;
  function compareNode(a, b) {
    return a && b ? compare(a.__data__, b.__data__) : !a - !b;
  }
  for (var groups = this._groups, m = groups.length, sortgroups = new Array(m), j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, sortgroup = sortgroups[j] = new Array(n), node, i = 0; i < n; ++i) {
      if (node = group[i]) {
        sortgroup[i] = node;
      }
    }
    sortgroup.sort(compareNode);
  }
  return new Selection$1$1(sortgroups, this._parents).order();
}
function ascending$1(a, b) {
  return a < b ? -1 : a > b ? 1 : a >= b ? 0 : NaN;
}
function selection_call$1() {
  var callback = arguments[0];
  arguments[0] = this;
  callback.apply(null, arguments);
  return this;
}
function selection_nodes$1() {
  return Array.from(this);
}
function selection_node$1() {
  for (var groups = this._groups, j = 0, m = groups.length; j < m; ++j) {
    for (var group = groups[j], i = 0, n = group.length; i < n; ++i) {
      var node = group[i];
      if (node) return node;
    }
  }
  return null;
}
function selection_size$1() {
  let size = 0;
  for (const node of this) ++size;
  return size;
}
function selection_empty$1() {
  return !this.node();
}
function selection_each$1(callback) {
  for (var groups = this._groups, j = 0, m = groups.length; j < m; ++j) {
    for (var group = groups[j], i = 0, n = group.length, node; i < n; ++i) {
      if (node = group[i]) callback.call(node, node.__data__, i, group);
    }
  }
  return this;
}
function attrRemove$1$1(name) {
  return function () {
    this.removeAttribute(name);
  };
}
function attrRemoveNS$1$1(fullname) {
  return function () {
    this.removeAttributeNS(fullname.space, fullname.local);
  };
}
function attrConstant$1$1(name, value) {
  return function () {
    this.setAttribute(name, value);
  };
}
function attrConstantNS$1$1(fullname, value) {
  return function () {
    this.setAttributeNS(fullname.space, fullname.local, value);
  };
}
function attrFunction$1$1(name, value) {
  return function () {
    var v = value.apply(this, arguments);
    if (v == null) this.removeAttribute(name);else this.setAttribute(name, v);
  };
}
function attrFunctionNS$1$1(fullname, value) {
  return function () {
    var v = value.apply(this, arguments);
    if (v == null) this.removeAttributeNS(fullname.space, fullname.local);else this.setAttributeNS(fullname.space, fullname.local, v);
  };
}
function selection_attr$1(name, value) {
  var fullname = namespace$1(name);
  if (arguments.length < 2) {
    var node = this.node();
    return fullname.local ? node.getAttributeNS(fullname.space, fullname.local) : node.getAttribute(fullname);
  }
  return this.each((value == null ? fullname.local ? attrRemoveNS$1$1 : attrRemove$1$1 : typeof value === "function" ? fullname.local ? attrFunctionNS$1$1 : attrFunction$1$1 : fullname.local ? attrConstantNS$1$1 : attrConstant$1$1)(fullname, value));
}
function defaultView$1(node) {
  return node.ownerDocument && node.ownerDocument.defaultView || node.document && node || node.defaultView;
}
function styleRemove$1$1(name) {
  return function () {
    this.style.removeProperty(name);
  };
}
function styleConstant$1$1(name, value, priority) {
  return function () {
    this.style.setProperty(name, value, priority);
  };
}
function styleFunction$1$1(name, value, priority) {
  return function () {
    var v = value.apply(this, arguments);
    if (v == null) this.style.removeProperty(name);else this.style.setProperty(name, v, priority);
  };
}
function selection_style$1(name, value, priority) {
  return arguments.length > 1 ? this.each((value == null ? styleRemove$1$1 : typeof value === "function" ? styleFunction$1$1 : styleConstant$1$1)(name, value, priority == null ? "" : priority)) : styleValue$1(this.node(), name);
}
function styleValue$1(node, name) {
  return node.style.getPropertyValue(name) || defaultView$1(node).getComputedStyle(node, null).getPropertyValue(name);
}
function propertyRemove$1(name) {
  return function () {
    delete this[name];
  };
}
function propertyConstant$1(name, value) {
  return function () {
    this[name] = value;
  };
}
function propertyFunction$1(name, value) {
  return function () {
    var v = value.apply(this, arguments);
    if (v == null) delete this[name];else this[name] = v;
  };
}
function selection_property$1(name, value) {
  return arguments.length > 1 ? this.each((value == null ? propertyRemove$1 : typeof value === "function" ? propertyFunction$1 : propertyConstant$1)(name, value)) : this.node()[name];
}
function classArray$1(string) {
  return string.trim().split(/^|\s+/);
}
function classList$1(node) {
  return node.classList || new ClassList$1(node);
}
function ClassList$1(node) {
  this._node = node;
  this._names = classArray$1(node.getAttribute("class") || "");
}
ClassList$1.prototype = {
  add: function add(name) {
    var i = this._names.indexOf(name);
    if (i < 0) {
      this._names.push(name);
      this._node.setAttribute("class", this._names.join(" "));
    }
  },
  remove: function remove(name) {
    var i = this._names.indexOf(name);
    if (i >= 0) {
      this._names.splice(i, 1);
      this._node.setAttribute("class", this._names.join(" "));
    }
  },
  contains: function contains(name) {
    return this._names.indexOf(name) >= 0;
  }
};
function classedAdd$1(node, names) {
  var list = classList$1(node),
    i = -1,
    n = names.length;
  while (++i < n) list.add(names[i]);
}
function classedRemove$1(node, names) {
  var list = classList$1(node),
    i = -1,
    n = names.length;
  while (++i < n) list.remove(names[i]);
}
function classedTrue$1(names) {
  return function () {
    classedAdd$1(this, names);
  };
}
function classedFalse$1(names) {
  return function () {
    classedRemove$1(this, names);
  };
}
function classedFunction$1(names, value) {
  return function () {
    (value.apply(this, arguments) ? classedAdd$1 : classedRemove$1)(this, names);
  };
}
function selection_classed$1(name, value) {
  var names = classArray$1(name + "");
  if (arguments.length < 2) {
    var list = classList$1(this.node()),
      i = -1,
      n = names.length;
    while (++i < n) if (!list.contains(names[i])) return false;
    return true;
  }
  return this.each((typeof value === "function" ? classedFunction$1 : value ? classedTrue$1 : classedFalse$1)(names, value));
}
function textRemove$1() {
  this.textContent = "";
}
function textConstant$1$1(value) {
  return function () {
    this.textContent = value;
  };
}
function textFunction$1$1(value) {
  return function () {
    var v = value.apply(this, arguments);
    this.textContent = v == null ? "" : v;
  };
}
function selection_text$1(value) {
  return arguments.length ? this.each(value == null ? textRemove$1 : (typeof value === "function" ? textFunction$1$1 : textConstant$1$1)(value)) : this.node().textContent;
}
function htmlRemove$1() {
  this.innerHTML = "";
}
function htmlConstant$1(value) {
  return function () {
    this.innerHTML = value;
  };
}
function htmlFunction$1(value) {
  return function () {
    var v = value.apply(this, arguments);
    this.innerHTML = v == null ? "" : v;
  };
}
function selection_html$1(value) {
  return arguments.length ? this.each(value == null ? htmlRemove$1 : (typeof value === "function" ? htmlFunction$1 : htmlConstant$1)(value)) : this.node().innerHTML;
}
function raise$1() {
  if (this.nextSibling) this.parentNode.appendChild(this);
}
function selection_raise$1() {
  return this.each(raise$1);
}
function lower$1() {
  if (this.previousSibling) this.parentNode.insertBefore(this, this.parentNode.firstChild);
}
function selection_lower$1() {
  return this.each(lower$1);
}
function selection_append$1(name) {
  var create2 = typeof name === "function" ? name : creator$1(name);
  return this.select(function () {
    return this.appendChild(create2.apply(this, arguments));
  });
}
function constantNull$1() {
  return null;
}
function selection_insert$1(name, before) {
  var create2 = typeof name === "function" ? name : creator$1(name),
    select2 = before == null ? constantNull$1 : typeof before === "function" ? before : selector$1(before);
  return this.select(function () {
    return this.insertBefore(create2.apply(this, arguments), select2.apply(this, arguments) || null);
  });
}
function remove$1() {
  var parent = this.parentNode;
  if (parent) parent.removeChild(this);
}
function selection_remove$1() {
  return this.each(remove$1);
}
function selection_cloneShallow$1() {
  var clone = this.cloneNode(false),
    parent = this.parentNode;
  return parent ? parent.insertBefore(clone, this.nextSibling) : clone;
}
function selection_cloneDeep$1() {
  var clone = this.cloneNode(true),
    parent = this.parentNode;
  return parent ? parent.insertBefore(clone, this.nextSibling) : clone;
}
function selection_clone$1(deep) {
  return this.select(deep ? selection_cloneDeep$1 : selection_cloneShallow$1);
}
function selection_datum$1(value) {
  return arguments.length ? this.property("__data__", value) : this.node().__data__;
}
function contextListener$1(listener) {
  return function (event) {
    listener.call(this, event, this.__data__);
  };
}
function parseTypenames$2(typenames) {
  return typenames.trim().split(/^|\s+/).map(function (t) {
    var name = "",
      i = t.indexOf(".");
    if (i >= 0) name = t.slice(i + 1), t = t.slice(0, i);
    return {
      type: t,
      name
    };
  });
}
function onRemove$1(typename) {
  return function () {
    var on = this.__on;
    if (!on) return;
    for (var j = 0, i = -1, m = on.length, o; j < m; ++j) {
      if (o = on[j], (!typename.type || o.type === typename.type) && o.name === typename.name) {
        this.removeEventListener(o.type, o.listener, o.options);
      } else {
        on[++i] = o;
      }
    }
    if (++i) on.length = i;else delete this.__on;
  };
}
function onAdd$1(typename, value, options) {
  return function () {
    var on = this.__on,
      o,
      listener = contextListener$1(value);
    if (on) for (var j = 0, m = on.length; j < m; ++j) {
      if ((o = on[j]).type === typename.type && o.name === typename.name) {
        this.removeEventListener(o.type, o.listener, o.options);
        this.addEventListener(o.type, o.listener = listener, o.options = options);
        o.value = value;
        return;
      }
    }
    this.addEventListener(typename.type, listener, options);
    o = {
      type: typename.type,
      name: typename.name,
      value,
      listener,
      options
    };
    if (!on) this.__on = [o];else on.push(o);
  };
}
function selection_on$1(typename, value, options) {
  var typenames = parseTypenames$2(typename + ""),
    i,
    n = typenames.length,
    t;
  if (arguments.length < 2) {
    var on = this.node().__on;
    if (on) for (var j = 0, m = on.length, o; j < m; ++j) {
      for (i = 0, o = on[j]; i < n; ++i) {
        if ((t = typenames[i]).type === o.type && t.name === o.name) {
          return o.value;
        }
      }
    }
    return;
  }
  on = value ? onAdd$1 : onRemove$1;
  for (i = 0; i < n; ++i) this.each(on(typenames[i], value, options));
  return this;
}
function dispatchEvent$1(node, type, params) {
  var window2 = defaultView$1(node),
    event = window2.CustomEvent;
  if (typeof event === "function") {
    event = new event(type, params);
  } else {
    event = window2.document.createEvent("Event");
    if (params) event.initEvent(type, params.bubbles, params.cancelable), event.detail = params.detail;else event.initEvent(type, false, false);
  }
  node.dispatchEvent(event);
}
function dispatchConstant$1(type, params) {
  return function () {
    return dispatchEvent$1(this, type, params);
  };
}
function dispatchFunction$1(type, params) {
  return function () {
    return dispatchEvent$1(this, type, params.apply(this, arguments));
  };
}
function selection_dispatch$1(type, params) {
  return this.each((typeof params === "function" ? dispatchFunction$1 : dispatchConstant$1)(type, params));
}
function* selection_iterator$1() {
  for (var groups = this._groups, j = 0, m = groups.length; j < m; ++j) {
    for (var group = groups[j], i = 0, n = group.length, node; i < n; ++i) {
      if (node = group[i]) yield node;
    }
  }
}
var root$1 = [null];
function Selection$1$1(groups, parents) {
  this._groups = groups;
  this._parents = parents;
}
function selection$1() {
  return new Selection$1$1([[document.documentElement]], root$1);
}
function selection_selection$1() {
  return this;
}
Selection$1$1.prototype = selection$1.prototype = {
  constructor: Selection$1$1,
  select: selection_select$1,
  selectAll: selection_selectAll$1,
  selectChild: selection_selectChild$1,
  selectChildren: selection_selectChildren$1,
  filter: selection_filter$1,
  data: selection_data$1,
  enter: selection_enter$1,
  exit: selection_exit$1,
  join: selection_join$1,
  merge: selection_merge$1,
  selection: selection_selection$1,
  order: selection_order$1,
  sort: selection_sort$1,
  call: selection_call$1,
  nodes: selection_nodes$1,
  node: selection_node$1,
  size: selection_size$1,
  empty: selection_empty$1,
  each: selection_each$1,
  attr: selection_attr$1,
  style: selection_style$1,
  property: selection_property$1,
  classed: selection_classed$1,
  text: selection_text$1,
  html: selection_html$1,
  raise: selection_raise$1,
  lower: selection_lower$1,
  append: selection_append$1,
  insert: selection_insert$1,
  remove: selection_remove$1,
  clone: selection_clone$1,
  datum: selection_datum$1,
  on: selection_on$1,
  dispatch: selection_dispatch$1,
  [Symbol.iterator]: selection_iterator$1
};
function select$1(selector2) {
  return typeof selector2 === "string" ? new Selection$1$1([[document.querySelector(selector2)]], [document.documentElement]) : new Selection$1$1([[selector2]], root$1);
}
function sourceEvent$1(event) {
  let sourceEvent2;
  while (sourceEvent2 = event.sourceEvent) event = sourceEvent2;
  return event;
}
function pointer$1(event, node) {
  event = sourceEvent$1(event);
  if (node === void 0) node = event.currentTarget;
  if (node) {
    var svg = node.ownerSVGElement || node;
    if (svg.createSVGPoint) {
      var point = svg.createSVGPoint();
      point.x = event.clientX, point.y = event.clientY;
      point = point.matrixTransform(node.getScreenCTM().inverse());
      return [point.x, point.y];
    }
    if (node.getBoundingClientRect) {
      var rect = node.getBoundingClientRect();
      return [event.clientX - rect.left - node.clientLeft, event.clientY - rect.top - node.clientTop];
    }
  }
  return [event.pageX, event.pageY];
}
const nonpassive = {
  passive: false
};
const nonpassivecapture$1 = {
  capture: true,
  passive: false
};
function nopropagation$1(event) {
  event.stopImmediatePropagation();
}
function noevent$1$1(event) {
  event.preventDefault();
  event.stopImmediatePropagation();
}
function dragDisable$1(view) {
  var root2 = view.document.documentElement,
    selection2 = select$1(view).on("dragstart.drag", noevent$1$1, nonpassivecapture$1);
  if ("onselectstart" in root2) {
    selection2.on("selectstart.drag", noevent$1$1, nonpassivecapture$1);
  } else {
    root2.__noselect = root2.style.MozUserSelect;
    root2.style.MozUserSelect = "none";
  }
}
function yesdrag$1(view, noclick) {
  var root2 = view.document.documentElement,
    selection2 = select$1(view).on("dragstart.drag", null);
  if (noclick) {
    selection2.on("click.drag", noevent$1$1, nonpassivecapture$1);
    setTimeout(function () {
      selection2.on("click.drag", null);
    }, 0);
  }
  if ("onselectstart" in root2) {
    selection2.on("selectstart.drag", null);
  } else {
    root2.style.MozUserSelect = root2.__noselect;
    delete root2.__noselect;
  }
}
const constant$2$1 = x => () => x;
function DragEvent(type, _ref5) {
  let sourceEvent2 = _ref5.sourceEvent,
    subject = _ref5.subject,
    target = _ref5.target,
    identifier = _ref5.identifier,
    active = _ref5.active,
    x = _ref5.x,
    y = _ref5.y,
    dx = _ref5.dx,
    dy = _ref5.dy,
    dispatch2 = _ref5.dispatch;
  Object.defineProperties(this, {
    type: {
      value: type,
      enumerable: true,
      configurable: true
    },
    sourceEvent: {
      value: sourceEvent2,
      enumerable: true,
      configurable: true
    },
    subject: {
      value: subject,
      enumerable: true,
      configurable: true
    },
    target: {
      value: target,
      enumerable: true,
      configurable: true
    },
    identifier: {
      value: identifier,
      enumerable: true,
      configurable: true
    },
    active: {
      value: active,
      enumerable: true,
      configurable: true
    },
    x: {
      value: x,
      enumerable: true,
      configurable: true
    },
    y: {
      value: y,
      enumerable: true,
      configurable: true
    },
    dx: {
      value: dx,
      enumerable: true,
      configurable: true
    },
    dy: {
      value: dy,
      enumerable: true,
      configurable: true
    },
    _: {
      value: dispatch2
    }
  });
}
DragEvent.prototype.on = function () {
  var value = this._.on.apply(this._, arguments);
  return value === this._ ? this : value;
};
function defaultFilter$1(event) {
  return !event.ctrlKey && !event.button;
}
function defaultContainer() {
  return this.parentNode;
}
function defaultSubject(event, d) {
  return d == null ? {
    x: event.x,
    y: event.y
  } : d;
}
function defaultTouchable$1() {
  return navigator.maxTouchPoints || "ontouchstart" in this;
}
function drag() {
  var filter2 = defaultFilter$1,
    container = defaultContainer,
    subject = defaultSubject,
    touchable = defaultTouchable$1,
    gestures = {},
    listeners = dispatch$1("start", "drag", "end"),
    active = 0,
    mousedownx,
    mousedowny,
    mousemoving,
    touchending,
    clickDistance2 = 0;
  function drag2(selection2) {
    selection2.on("mousedown.drag", mousedowned).filter(touchable).on("touchstart.drag", touchstarted).on("touchmove.drag", touchmoved, nonpassive).on("touchend.drag touchcancel.drag", touchended).style("touch-action", "none").style("-webkit-tap-highlight-color", "rgba(0,0,0,0)");
  }
  function mousedowned(event, d) {
    if (touchending || !filter2.call(this, event, d)) return;
    var gesture = beforestart(this, container.call(this, event, d), event, d, "mouse");
    if (!gesture) return;
    select$1(event.view).on("mousemove.drag", mousemoved, nonpassivecapture$1).on("mouseup.drag", mouseupped, nonpassivecapture$1);
    dragDisable$1(event.view);
    nopropagation$1(event);
    mousemoving = false;
    mousedownx = event.clientX;
    mousedowny = event.clientY;
    gesture("start", event);
  }
  function mousemoved(event) {
    noevent$1$1(event);
    if (!mousemoving) {
      var dx = event.clientX - mousedownx,
        dy = event.clientY - mousedowny;
      mousemoving = dx * dx + dy * dy > clickDistance2;
    }
    gestures.mouse("drag", event);
  }
  function mouseupped(event) {
    select$1(event.view).on("mousemove.drag mouseup.drag", null);
    yesdrag$1(event.view, mousemoving);
    noevent$1$1(event);
    gestures.mouse("end", event);
  }
  function touchstarted(event, d) {
    if (!filter2.call(this, event, d)) return;
    var touches = event.changedTouches,
      c = container.call(this, event, d),
      n = touches.length,
      i,
      gesture;
    for (i = 0; i < n; ++i) {
      if (gesture = beforestart(this, c, event, d, touches[i].identifier, touches[i])) {
        nopropagation$1(event);
        gesture("start", event, touches[i]);
      }
    }
  }
  function touchmoved(event) {
    var touches = event.changedTouches,
      n = touches.length,
      i,
      gesture;
    for (i = 0; i < n; ++i) {
      if (gesture = gestures[touches[i].identifier]) {
        noevent$1$1(event);
        gesture("drag", event, touches[i]);
      }
    }
  }
  function touchended(event) {
    var touches = event.changedTouches,
      n = touches.length,
      i,
      gesture;
    if (touchending) clearTimeout(touchending);
    touchending = setTimeout(function () {
      touchending = null;
    }, 500);
    for (i = 0; i < n; ++i) {
      if (gesture = gestures[touches[i].identifier]) {
        nopropagation$1(event);
        gesture("end", event, touches[i]);
      }
    }
  }
  function beforestart(that, container2, event, d, identifier, touch) {
    var dispatch2 = listeners.copy(),
      p = pointer$1(touch || event, container2),
      dx,
      dy,
      s;
    if ((s = subject.call(that, new DragEvent("beforestart", {
      sourceEvent: event,
      target: drag2,
      identifier,
      active,
      x: p[0],
      y: p[1],
      dx: 0,
      dy: 0,
      dispatch: dispatch2
    }), d)) == null) return;
    dx = s.x - p[0] || 0;
    dy = s.y - p[1] || 0;
    return function gesture(type, event2, touch2) {
      var p0 = p,
        n;
      switch (type) {
        case "start":
          gestures[identifier] = gesture, n = active++;
          break;
        case "end":
          delete gestures[identifier], --active;
        case "drag":
          p = pointer$1(touch2 || event2, container2), n = active;
          break;
      }
      dispatch2.call(type, that, new DragEvent(type, {
        sourceEvent: event2,
        subject: s,
        target: drag2,
        identifier,
        active: n,
        x: p[0] + dx,
        y: p[1] + dy,
        dx: p[0] - p0[0],
        dy: p[1] - p0[1],
        dispatch: dispatch2
      }), d);
    };
  }
  drag2.filter = function (_) {
    return arguments.length ? (filter2 = typeof _ === "function" ? _ : constant$2$1(!!_), drag2) : filter2;
  };
  drag2.container = function (_) {
    return arguments.length ? (container = typeof _ === "function" ? _ : constant$2$1(_), drag2) : container;
  };
  drag2.subject = function (_) {
    return arguments.length ? (subject = typeof _ === "function" ? _ : constant$2$1(_), drag2) : subject;
  };
  drag2.touchable = function (_) {
    return arguments.length ? (touchable = typeof _ === "function" ? _ : constant$2$1(!!_), drag2) : touchable;
  };
  drag2.on = function () {
    var value = listeners.on.apply(listeners, arguments);
    return value === listeners ? drag2 : value;
  };
  drag2.clickDistance = function (_) {
    return arguments.length ? (clickDistance2 = (_ = +_) * _, drag2) : Math.sqrt(clickDistance2);
  };
  return drag2;
}
function define$1(constructor, factory, prototype) {
  constructor.prototype = factory.prototype = prototype;
  prototype.constructor = constructor;
}
function extend$1(parent, definition) {
  var prototype = Object.create(parent.prototype);
  for (var key in definition) prototype[key] = definition[key];
  return prototype;
}
function Color$1() {}
var darker$1 = 0.7;
var brighter$1 = 1 / darker$1;
var reI$1 = "\\s*([+-]?\\d+)\\s*",
  reN$1 = "\\s*([+-]?(?:\\d*\\.)?\\d+(?:[eE][+-]?\\d+)?)\\s*",
  reP$1 = "\\s*([+-]?(?:\\d*\\.)?\\d+(?:[eE][+-]?\\d+)?)%\\s*",
  reHex$1 = /^#([0-9a-f]{3,8})$/,
  reRgbInteger$1 = new RegExp("^rgb\\(" + reI$1 + "," + reI$1 + "," + reI$1 + "\\)$"),
  reRgbPercent$1 = new RegExp("^rgb\\(" + reP$1 + "," + reP$1 + "," + reP$1 + "\\)$"),
  reRgbaInteger$1 = new RegExp("^rgba\\(" + reI$1 + "," + reI$1 + "," + reI$1 + "," + reN$1 + "\\)$"),
  reRgbaPercent$1 = new RegExp("^rgba\\(" + reP$1 + "," + reP$1 + "," + reP$1 + "," + reN$1 + "\\)$"),
  reHslPercent$1 = new RegExp("^hsl\\(" + reN$1 + "," + reP$1 + "," + reP$1 + "\\)$"),
  reHslaPercent$1 = new RegExp("^hsla\\(" + reN$1 + "," + reP$1 + "," + reP$1 + "," + reN$1 + "\\)$");
var named$1 = {
  aliceblue: 15792383,
  antiquewhite: 16444375,
  aqua: 65535,
  aquamarine: 8388564,
  azure: 15794175,
  beige: 16119260,
  bisque: 16770244,
  black: 0,
  blanchedalmond: 16772045,
  blue: 255,
  blueviolet: 9055202,
  brown: 10824234,
  burlywood: 14596231,
  cadetblue: 6266528,
  chartreuse: 8388352,
  chocolate: 13789470,
  coral: 16744272,
  cornflowerblue: 6591981,
  cornsilk: 16775388,
  crimson: 14423100,
  cyan: 65535,
  darkblue: 139,
  darkcyan: 35723,
  darkgoldenrod: 12092939,
  darkgray: 11119017,
  darkgreen: 25600,
  darkgrey: 11119017,
  darkkhaki: 12433259,
  darkmagenta: 9109643,
  darkolivegreen: 5597999,
  darkorange: 16747520,
  darkorchid: 10040012,
  darkred: 9109504,
  darksalmon: 15308410,
  darkseagreen: 9419919,
  darkslateblue: 4734347,
  darkslategray: 3100495,
  darkslategrey: 3100495,
  darkturquoise: 52945,
  darkviolet: 9699539,
  deeppink: 16716947,
  deepskyblue: 49151,
  dimgray: 6908265,
  dimgrey: 6908265,
  dodgerblue: 2003199,
  firebrick: 11674146,
  floralwhite: 16775920,
  forestgreen: 2263842,
  fuchsia: 16711935,
  gainsboro: 14474460,
  ghostwhite: 16316671,
  gold: 16766720,
  goldenrod: 14329120,
  gray: 8421504,
  green: 32768,
  greenyellow: 11403055,
  grey: 8421504,
  honeydew: 15794160,
  hotpink: 16738740,
  indianred: 13458524,
  indigo: 4915330,
  ivory: 16777200,
  khaki: 15787660,
  lavender: 15132410,
  lavenderblush: 16773365,
  lawngreen: 8190976,
  lemonchiffon: 16775885,
  lightblue: 11393254,
  lightcoral: 15761536,
  lightcyan: 14745599,
  lightgoldenrodyellow: 16448210,
  lightgray: 13882323,
  lightgreen: 9498256,
  lightgrey: 13882323,
  lightpink: 16758465,
  lightsalmon: 16752762,
  lightseagreen: 2142890,
  lightskyblue: 8900346,
  lightslategray: 7833753,
  lightslategrey: 7833753,
  lightsteelblue: 11584734,
  lightyellow: 16777184,
  lime: 65280,
  limegreen: 3329330,
  linen: 16445670,
  magenta: 16711935,
  maroon: 8388608,
  mediumaquamarine: 6737322,
  mediumblue: 205,
  mediumorchid: 12211667,
  mediumpurple: 9662683,
  mediumseagreen: 3978097,
  mediumslateblue: 8087790,
  mediumspringgreen: 64154,
  mediumturquoise: 4772300,
  mediumvioletred: 13047173,
  midnightblue: 1644912,
  mintcream: 16121850,
  mistyrose: 16770273,
  moccasin: 16770229,
  navajowhite: 16768685,
  navy: 128,
  oldlace: 16643558,
  olive: 8421376,
  olivedrab: 7048739,
  orange: 16753920,
  orangered: 16729344,
  orchid: 14315734,
  palegoldenrod: 15657130,
  palegreen: 10025880,
  paleturquoise: 11529966,
  palevioletred: 14381203,
  papayawhip: 16773077,
  peachpuff: 16767673,
  peru: 13468991,
  pink: 16761035,
  plum: 14524637,
  powderblue: 11591910,
  purple: 8388736,
  rebeccapurple: 6697881,
  red: 16711680,
  rosybrown: 12357519,
  royalblue: 4286945,
  saddlebrown: 9127187,
  salmon: 16416882,
  sandybrown: 16032864,
  seagreen: 3050327,
  seashell: 16774638,
  sienna: 10506797,
  silver: 12632256,
  skyblue: 8900331,
  slateblue: 6970061,
  slategray: 7372944,
  slategrey: 7372944,
  snow: 16775930,
  springgreen: 65407,
  steelblue: 4620980,
  tan: 13808780,
  teal: 32896,
  thistle: 14204888,
  tomato: 16737095,
  turquoise: 4251856,
  violet: 15631086,
  wheat: 16113331,
  white: 16777215,
  whitesmoke: 16119285,
  yellow: 16776960,
  yellowgreen: 10145074
};
define$1(Color$1, color$1, {
  copy(channels) {
    return Object.assign(new this.constructor(), this, channels);
  },
  displayable() {
    return this.rgb().displayable();
  },
  hex: color_formatHex$1,
  // Deprecated! Use color.formatHex.
  formatHex: color_formatHex$1,
  formatHex8: color_formatHex8$1,
  formatHsl: color_formatHsl$1,
  formatRgb: color_formatRgb$1,
  toString: color_formatRgb$1
});
function color_formatHex$1() {
  return this.rgb().formatHex();
}
function color_formatHex8$1() {
  return this.rgb().formatHex8();
}
function color_formatHsl$1() {
  return hslConvert$1(this).formatHsl();
}
function color_formatRgb$1() {
  return this.rgb().formatRgb();
}
function color$1(format) {
  var m, l;
  format = (format + "").trim().toLowerCase();
  return (m = reHex$1.exec(format)) ? (l = m[1].length, m = parseInt(m[1], 16), l === 6 ? rgbn$1(m) : l === 3 ? new Rgb$1(m >> 8 & 15 | m >> 4 & 240, m >> 4 & 15 | m & 240, (m & 15) << 4 | m & 15, 1) : l === 8 ? rgba$1(m >> 24 & 255, m >> 16 & 255, m >> 8 & 255, (m & 255) / 255) : l === 4 ? rgba$1(m >> 12 & 15 | m >> 8 & 240, m >> 8 & 15 | m >> 4 & 240, m >> 4 & 15 | m & 240, ((m & 15) << 4 | m & 15) / 255) : null) : (m = reRgbInteger$1.exec(format)) ? new Rgb$1(m[1], m[2], m[3], 1) : (m = reRgbPercent$1.exec(format)) ? new Rgb$1(m[1] * 255 / 100, m[2] * 255 / 100, m[3] * 255 / 100, 1) : (m = reRgbaInteger$1.exec(format)) ? rgba$1(m[1], m[2], m[3], m[4]) : (m = reRgbaPercent$1.exec(format)) ? rgba$1(m[1] * 255 / 100, m[2] * 255 / 100, m[3] * 255 / 100, m[4]) : (m = reHslPercent$1.exec(format)) ? hsla$1(m[1], m[2] / 100, m[3] / 100, 1) : (m = reHslaPercent$1.exec(format)) ? hsla$1(m[1], m[2] / 100, m[3] / 100, m[4]) : named$1.hasOwnProperty(format) ? rgbn$1(named$1[format]) : format === "transparent" ? new Rgb$1(NaN, NaN, NaN, 0) : null;
}
function rgbn$1(n) {
  return new Rgb$1(n >> 16 & 255, n >> 8 & 255, n & 255, 1);
}
function rgba$1(r, g, b, a) {
  if (a <= 0) r = g = b = NaN;
  return new Rgb$1(r, g, b, a);
}
function rgbConvert$1(o) {
  if (!(o instanceof Color$1)) o = color$1(o);
  if (!o) return new Rgb$1();
  o = o.rgb();
  return new Rgb$1(o.r, o.g, o.b, o.opacity);
}
function rgb$1(r, g, b, opacity) {
  return arguments.length === 1 ? rgbConvert$1(r) : new Rgb$1(r, g, b, opacity == null ? 1 : opacity);
}
function Rgb$1(r, g, b, opacity) {
  this.r = +r;
  this.g = +g;
  this.b = +b;
  this.opacity = +opacity;
}
define$1(Rgb$1, rgb$1, extend$1(Color$1, {
  brighter(k) {
    k = k == null ? brighter$1 : Math.pow(brighter$1, k);
    return new Rgb$1(this.r * k, this.g * k, this.b * k, this.opacity);
  },
  darker(k) {
    k = k == null ? darker$1 : Math.pow(darker$1, k);
    return new Rgb$1(this.r * k, this.g * k, this.b * k, this.opacity);
  },
  rgb() {
    return this;
  },
  clamp() {
    return new Rgb$1(clampi$1(this.r), clampi$1(this.g), clampi$1(this.b), clampa$1(this.opacity));
  },
  displayable() {
    return -0.5 <= this.r && this.r < 255.5 && -0.5 <= this.g && this.g < 255.5 && -0.5 <= this.b && this.b < 255.5 && 0 <= this.opacity && this.opacity <= 1;
  },
  hex: rgb_formatHex$1,
  // Deprecated! Use color.formatHex.
  formatHex: rgb_formatHex$1,
  formatHex8: rgb_formatHex8$1,
  formatRgb: rgb_formatRgb$1,
  toString: rgb_formatRgb$1
}));
function rgb_formatHex$1() {
  return "#" + hex$1(this.r) + hex$1(this.g) + hex$1(this.b);
}
function rgb_formatHex8$1() {
  return "#" + hex$1(this.r) + hex$1(this.g) + hex$1(this.b) + hex$1((isNaN(this.opacity) ? 1 : this.opacity) * 255);
}
function rgb_formatRgb$1() {
  const a = clampa$1(this.opacity);
  return "" + (a === 1 ? "rgb(" : "rgba(") + clampi$1(this.r) + ", " + clampi$1(this.g) + ", " + clampi$1(this.b) + (a === 1 ? ")" : ", " + a + ")");
}
function clampa$1(opacity) {
  return isNaN(opacity) ? 1 : Math.max(0, Math.min(1, opacity));
}
function clampi$1(value) {
  return Math.max(0, Math.min(255, Math.round(value) || 0));
}
function hex$1(value) {
  value = clampi$1(value);
  return (value < 16 ? "0" : "") + value.toString(16);
}
function hsla$1(h2, s, l, a) {
  if (a <= 0) h2 = s = l = NaN;else if (l <= 0 || l >= 1) h2 = s = NaN;else if (s <= 0) h2 = NaN;
  return new Hsl$1(h2, s, l, a);
}
function hslConvert$1(o) {
  if (o instanceof Hsl$1) return new Hsl$1(o.h, o.s, o.l, o.opacity);
  if (!(o instanceof Color$1)) o = color$1(o);
  if (!o) return new Hsl$1();
  if (o instanceof Hsl$1) return o;
  o = o.rgb();
  var r = o.r / 255,
    g = o.g / 255,
    b = o.b / 255,
    min = Math.min(r, g, b),
    max = Math.max(r, g, b),
    h2 = NaN,
    s = max - min,
    l = (max + min) / 2;
  if (s) {
    if (r === max) h2 = (g - b) / s + (g < b) * 6;else if (g === max) h2 = (b - r) / s + 2;else h2 = (r - g) / s + 4;
    s /= l < 0.5 ? max + min : 2 - max - min;
    h2 *= 60;
  } else {
    s = l > 0 && l < 1 ? 0 : h2;
  }
  return new Hsl$1(h2, s, l, o.opacity);
}
function hsl$1(h2, s, l, opacity) {
  return arguments.length === 1 ? hslConvert$1(h2) : new Hsl$1(h2, s, l, opacity == null ? 1 : opacity);
}
function Hsl$1(h2, s, l, opacity) {
  this.h = +h2;
  this.s = +s;
  this.l = +l;
  this.opacity = +opacity;
}
define$1(Hsl$1, hsl$1, extend$1(Color$1, {
  brighter(k) {
    k = k == null ? brighter$1 : Math.pow(brighter$1, k);
    return new Hsl$1(this.h, this.s, this.l * k, this.opacity);
  },
  darker(k) {
    k = k == null ? darker$1 : Math.pow(darker$1, k);
    return new Hsl$1(this.h, this.s, this.l * k, this.opacity);
  },
  rgb() {
    var h2 = this.h % 360 + (this.h < 0) * 360,
      s = isNaN(h2) || isNaN(this.s) ? 0 : this.s,
      l = this.l,
      m2 = l + (l < 0.5 ? l : 1 - l) * s,
      m1 = 2 * l - m2;
    return new Rgb$1(hsl2rgb$1(h2 >= 240 ? h2 - 240 : h2 + 120, m1, m2), hsl2rgb$1(h2, m1, m2), hsl2rgb$1(h2 < 120 ? h2 + 240 : h2 - 120, m1, m2), this.opacity);
  },
  clamp() {
    return new Hsl$1(clamph$1(this.h), clampt$1(this.s), clampt$1(this.l), clampa$1(this.opacity));
  },
  displayable() {
    return (0 <= this.s && this.s <= 1 || isNaN(this.s)) && 0 <= this.l && this.l <= 1 && 0 <= this.opacity && this.opacity <= 1;
  },
  formatHsl() {
    const a = clampa$1(this.opacity);
    return "" + (a === 1 ? "hsl(" : "hsla(") + clamph$1(this.h) + ", " + clampt$1(this.s) * 100 + "%, " + clampt$1(this.l) * 100 + "%" + (a === 1 ? ")" : ", " + a + ")");
  }
}));
function clamph$1(value) {
  value = (value || 0) % 360;
  return value < 0 ? value + 360 : value;
}
function clampt$1(value) {
  return Math.max(0, Math.min(1, value || 0));
}
function hsl2rgb$1(h2, m1, m2) {
  return (h2 < 60 ? m1 + (m2 - m1) * h2 / 60 : h2 < 180 ? m2 : h2 < 240 ? m1 + (m2 - m1) * (240 - h2) / 60 : m1) * 255;
}
const constant$1$1 = x => () => x;
function linear$1(a, d) {
  return function (t) {
    return a + t * d;
  };
}
function exponential$1(a, b, y) {
  return a = Math.pow(a, y), b = Math.pow(b, y) - a, y = 1 / y, function (t) {
    return Math.pow(a + t * b, y);
  };
}
function gamma$1(y) {
  return (y = +y) === 1 ? nogamma$1 : function (a, b) {
    return b - a ? exponential$1(a, b, y) : constant$1$1(isNaN(a) ? b : a);
  };
}
function nogamma$1(a, b) {
  var d = b - a;
  return d ? linear$1(a, d) : constant$1$1(isNaN(a) ? b : a);
}
const interpolateRgb$1 = function rgbGamma(y) {
  var color2 = gamma$1(y);
  function rgb$1$1(start2, end) {
    var r = color2((start2 = rgb$1(start2)).r, (end = rgb$1(end)).r),
      g = color2(start2.g, end.g),
      b = color2(start2.b, end.b),
      opacity = nogamma$1(start2.opacity, end.opacity);
    return function (t) {
      start2.r = r(t);
      start2.g = g(t);
      start2.b = b(t);
      start2.opacity = opacity(t);
      return start2 + "";
    };
  }
  rgb$1$1.gamma = rgbGamma;
  return rgb$1$1;
}(1);
function numberArray(a, b) {
  if (!b) b = [];
  var n = a ? Math.min(b.length, a.length) : 0,
    c = b.slice(),
    i;
  return function (t) {
    for (i = 0; i < n; ++i) c[i] = a[i] * (1 - t) + b[i] * t;
    return c;
  };
}
function isNumberArray(x) {
  return ArrayBuffer.isView(x) && !(x instanceof DataView);
}
function genericArray(a, b) {
  var nb = b ? b.length : 0,
    na = a ? Math.min(nb, a.length) : 0,
    x = new Array(na),
    c = new Array(nb),
    i;
  for (i = 0; i < na; ++i) x[i] = interpolate$1(a[i], b[i]);
  for (; i < nb; ++i) c[i] = b[i];
  return function (t) {
    for (i = 0; i < na; ++i) c[i] = x[i](t);
    return c;
  };
}
function date(a, b) {
  var d = /* @__PURE__ */new Date();
  return a = +a, b = +b, function (t) {
    return d.setTime(a * (1 - t) + b * t), d;
  };
}
function interpolateNumber$1(a, b) {
  return a = +a, b = +b, function (t) {
    return a * (1 - t) + b * t;
  };
}
function object(a, b) {
  var i = {},
    c = {},
    k;
  if (a === null || typeof a !== "object") a = {};
  if (b === null || typeof b !== "object") b = {};
  for (k in b) {
    if (k in a) {
      i[k] = interpolate$1(a[k], b[k]);
    } else {
      c[k] = b[k];
    }
  }
  return function (t) {
    for (k in i) c[k] = i[k](t);
    return c;
  };
}
var reA$1 = /[-+]?(?:\d+\.?\d*|\.?\d+)(?:[eE][-+]?\d+)?/g,
  reB$1 = new RegExp(reA$1.source, "g");
function zero$1(b) {
  return function () {
    return b;
  };
}
function one$1(b) {
  return function (t) {
    return b(t) + "";
  };
}
function interpolateString$1(a, b) {
  var bi = reA$1.lastIndex = reB$1.lastIndex = 0,
    am,
    bm,
    bs,
    i = -1,
    s = [],
    q = [];
  a = a + "", b = b + "";
  while ((am = reA$1.exec(a)) && (bm = reB$1.exec(b))) {
    if ((bs = bm.index) > bi) {
      bs = b.slice(bi, bs);
      if (s[i]) s[i] += bs;else s[++i] = bs;
    }
    if ((am = am[0]) === (bm = bm[0])) {
      if (s[i]) s[i] += bm;else s[++i] = bm;
    } else {
      s[++i] = null;
      q.push({
        i,
        x: interpolateNumber$1(am, bm)
      });
    }
    bi = reB$1.lastIndex;
  }
  if (bi < b.length) {
    bs = b.slice(bi);
    if (s[i]) s[i] += bs;else s[++i] = bs;
  }
  return s.length < 2 ? q[0] ? one$1(q[0].x) : zero$1(b) : (b = q.length, function (t) {
    for (var i2 = 0, o; i2 < b; ++i2) s[(o = q[i2]).i] = o.x(t);
    return s.join("");
  });
}
function interpolate$1(a, b) {
  var t = typeof b,
    c;
  return b == null || t === "boolean" ? constant$1$1(b) : (t === "number" ? interpolateNumber$1 : t === "string" ? (c = color$1(b)) ? (b = c, interpolateRgb$1) : interpolateString$1 : b instanceof color$1 ? interpolateRgb$1 : b instanceof Date ? date : isNumberArray(b) ? numberArray : Array.isArray(b) ? genericArray : typeof b.valueOf !== "function" && typeof b.toString !== "function" || isNaN(b) ? object : interpolateNumber$1)(a, b);
}
var degrees$1 = 180 / Math.PI;
var identity$1$1 = {
  translateX: 0,
  translateY: 0,
  rotate: 0,
  skewX: 0,
  scaleX: 1,
  scaleY: 1
};
function decompose$1(a, b, c, d, e, f) {
  var scaleX, scaleY, skewX;
  if (scaleX = Math.sqrt(a * a + b * b)) a /= scaleX, b /= scaleX;
  if (skewX = a * c + b * d) c -= a * skewX, d -= b * skewX;
  if (scaleY = Math.sqrt(c * c + d * d)) c /= scaleY, d /= scaleY, skewX /= scaleY;
  if (a * d < b * c) a = -a, b = -b, skewX = -skewX, scaleX = -scaleX;
  return {
    translateX: e,
    translateY: f,
    rotate: Math.atan2(b, a) * degrees$1,
    skewX: Math.atan(skewX) * degrees$1,
    scaleX,
    scaleY
  };
}
var svgNode$1;
function parseCss$1(value) {
  const m = new (typeof DOMMatrix === "function" ? DOMMatrix : WebKitCSSMatrix)(value + "");
  return m.isIdentity ? identity$1$1 : decompose$1(m.a, m.b, m.c, m.d, m.e, m.f);
}
function parseSvg$1(value) {
  if (value == null) return identity$1$1;
  if (!svgNode$1) svgNode$1 = document.createElementNS("http://www.w3.org/2000/svg", "g");
  svgNode$1.setAttribute("transform", value);
  if (!(value = svgNode$1.transform.baseVal.consolidate())) return identity$1$1;
  value = value.matrix;
  return decompose$1(value.a, value.b, value.c, value.d, value.e, value.f);
}
function interpolateTransform$1(parse, pxComma, pxParen, degParen) {
  function pop(s) {
    return s.length ? s.pop() + " " : "";
  }
  function translate(xa, ya, xb, yb, s, q) {
    if (xa !== xb || ya !== yb) {
      var i = s.push("translate(", null, pxComma, null, pxParen);
      q.push({
        i: i - 4,
        x: interpolateNumber$1(xa, xb)
      }, {
        i: i - 2,
        x: interpolateNumber$1(ya, yb)
      });
    } else if (xb || yb) {
      s.push("translate(" + xb + pxComma + yb + pxParen);
    }
  }
  function rotate(a, b, s, q) {
    if (a !== b) {
      if (a - b > 180) b += 360;else if (b - a > 180) a += 360;
      q.push({
        i: s.push(pop(s) + "rotate(", null, degParen) - 2,
        x: interpolateNumber$1(a, b)
      });
    } else if (b) {
      s.push(pop(s) + "rotate(" + b + degParen);
    }
  }
  function skewX(a, b, s, q) {
    if (a !== b) {
      q.push({
        i: s.push(pop(s) + "skewX(", null, degParen) - 2,
        x: interpolateNumber$1(a, b)
      });
    } else if (b) {
      s.push(pop(s) + "skewX(" + b + degParen);
    }
  }
  function scale(xa, ya, xb, yb, s, q) {
    if (xa !== xb || ya !== yb) {
      var i = s.push(pop(s) + "scale(", null, ",", null, ")");
      q.push({
        i: i - 4,
        x: interpolateNumber$1(xa, xb)
      }, {
        i: i - 2,
        x: interpolateNumber$1(ya, yb)
      });
    } else if (xb !== 1 || yb !== 1) {
      s.push(pop(s) + "scale(" + xb + "," + yb + ")");
    }
  }
  return function (a, b) {
    var s = [],
      q = [];
    a = parse(a), b = parse(b);
    translate(a.translateX, a.translateY, b.translateX, b.translateY, s, q);
    rotate(a.rotate, b.rotate, s, q);
    skewX(a.skewX, b.skewX, s, q);
    scale(a.scaleX, a.scaleY, b.scaleX, b.scaleY, s, q);
    a = b = null;
    return function (t) {
      var i = -1,
        n = q.length,
        o;
      while (++i < n) s[(o = q[i]).i] = o.x(t);
      return s.join("");
    };
  };
}
var interpolateTransformCss$1 = interpolateTransform$1(parseCss$1, "px, ", "px)", "deg)");
var interpolateTransformSvg$1 = interpolateTransform$1(parseSvg$1, ", ", ")", ")");
var epsilon2$1 = 1e-12;
function cosh$1(x) {
  return ((x = Math.exp(x)) + 1 / x) / 2;
}
function sinh$1(x) {
  return ((x = Math.exp(x)) - 1 / x) / 2;
}
function tanh$1(x) {
  return ((x = Math.exp(2 * x)) - 1) / (x + 1);
}
const interpolateZoom$1 = function zoomRho(rho, rho2, rho4) {
  function zoom2(p0, p1) {
    var ux0 = p0[0],
      uy0 = p0[1],
      w0 = p0[2],
      ux1 = p1[0],
      uy1 = p1[1],
      w1 = p1[2],
      dx = ux1 - ux0,
      dy = uy1 - uy0,
      d2 = dx * dx + dy * dy,
      i,
      S;
    if (d2 < epsilon2$1) {
      S = Math.log(w1 / w0) / rho;
      i = function i(t) {
        return [ux0 + t * dx, uy0 + t * dy, w0 * Math.exp(rho * t * S)];
      };
    } else {
      var d1 = Math.sqrt(d2),
        b0 = (w1 * w1 - w0 * w0 + rho4 * d2) / (2 * w0 * rho2 * d1),
        b1 = (w1 * w1 - w0 * w0 - rho4 * d2) / (2 * w1 * rho2 * d1),
        r0 = Math.log(Math.sqrt(b0 * b0 + 1) - b0),
        r1 = Math.log(Math.sqrt(b1 * b1 + 1) - b1);
      S = (r1 - r0) / rho;
      i = function i(t) {
        var s = t * S,
          coshr0 = cosh$1(r0),
          u = w0 / (rho2 * d1) * (coshr0 * tanh$1(rho * s + r0) - sinh$1(r0));
        return [ux0 + u * dx, uy0 + u * dy, w0 * coshr0 / cosh$1(rho * s + r0)];
      };
    }
    i.duration = S * 1e3 * rho / Math.SQRT2;
    return i;
  }
  zoom2.rho = function (_) {
    var _1 = Math.max(1e-3, +_),
      _2 = _1 * _1,
      _4 = _2 * _2;
    return zoomRho(_1, _2, _4);
  };
  return zoom2;
}(Math.SQRT2, 2, 4);
var frame$1 = 0,
  timeout$1$1 = 0,
  interval$1 = 0,
  pokeDelay$1 = 1e3,
  taskHead$1,
  taskTail$1,
  clockLast$1 = 0,
  clockNow$1 = 0,
  clockSkew$1 = 0,
  clock$1 = typeof performance === "object" && performance.now ? performance : Date,
  setFrame$1 = typeof window === "object" && window.requestAnimationFrame ? window.requestAnimationFrame.bind(window) : function (f) {
    setTimeout(f, 17);
  };
function now$1() {
  return clockNow$1 || (setFrame$1(clearNow$1), clockNow$1 = clock$1.now() + clockSkew$1);
}
function clearNow$1() {
  clockNow$1 = 0;
}
function Timer$1() {
  this._call = this._time = this._next = null;
}
Timer$1.prototype = timer$1.prototype = {
  constructor: Timer$1,
  restart: function restart(callback, delay, time) {
    if (typeof callback !== "function") throw new TypeError("callback is not a function");
    time = (time == null ? now$1() : +time) + (delay == null ? 0 : +delay);
    if (!this._next && taskTail$1 !== this) {
      if (taskTail$1) taskTail$1._next = this;else taskHead$1 = this;
      taskTail$1 = this;
    }
    this._call = callback;
    this._time = time;
    sleep$1();
  },
  stop: function stop() {
    if (this._call) {
      this._call = null;
      this._time = Infinity;
      sleep$1();
    }
  }
};
function timer$1(callback, delay, time) {
  var t = new Timer$1();
  t.restart(callback, delay, time);
  return t;
}
function timerFlush$1() {
  now$1();
  ++frame$1;
  var t = taskHead$1,
    e;
  while (t) {
    if ((e = clockNow$1 - t._time) >= 0) t._call.call(void 0, e);
    t = t._next;
  }
  --frame$1;
}
function wake$1() {
  clockNow$1 = (clockLast$1 = clock$1.now()) + clockSkew$1;
  frame$1 = timeout$1$1 = 0;
  try {
    timerFlush$1();
  } finally {
    frame$1 = 0;
    nap$1();
    clockNow$1 = 0;
  }
}
function poke$1() {
  var now2 = clock$1.now(),
    delay = now2 - clockLast$1;
  if (delay > pokeDelay$1) clockSkew$1 -= delay, clockLast$1 = now2;
}
function nap$1() {
  var t0,
    t1 = taskHead$1,
    t2,
    time = Infinity;
  while (t1) {
    if (t1._call) {
      if (time > t1._time) time = t1._time;
      t0 = t1, t1 = t1._next;
    } else {
      t2 = t1._next, t1._next = null;
      t1 = t0 ? t0._next = t2 : taskHead$1 = t2;
    }
  }
  taskTail$1 = t0;
  sleep$1(time);
}
function sleep$1(time) {
  if (frame$1) return;
  if (timeout$1$1) timeout$1$1 = clearTimeout(timeout$1$1);
  var delay = time - clockNow$1;
  if (delay > 24) {
    if (time < Infinity) timeout$1$1 = setTimeout(wake$1, time - clock$1.now() - clockSkew$1);
    if (interval$1) interval$1 = clearInterval(interval$1);
  } else {
    if (!interval$1) clockLast$1 = clock$1.now(), interval$1 = setInterval(poke$1, pokeDelay$1);
    frame$1 = 1, setFrame$1(wake$1);
  }
}
function timeout$2(callback, delay, time) {
  var t = new Timer$1();
  delay = delay == null ? 0 : +delay;
  t.restart(elapsed => {
    t.stop();
    callback(elapsed + delay);
  }, delay, time);
  return t;
}
var emptyOn$1 = dispatch$1("start", "end", "cancel", "interrupt");
var emptyTween$1 = [];
var CREATED$1 = 0;
var SCHEDULED$1 = 1;
var STARTING$1 = 2;
var STARTED$1 = 3;
var RUNNING$1 = 4;
var ENDING$1 = 5;
var ENDED$1 = 6;
function schedule$1(node, name, id2, index, group, timing) {
  var schedules = node.__transition;
  if (!schedules) node.__transition = {};else if (id2 in schedules) return;
  create$1(node, id2, {
    name,
    index,
    // For context during callback.
    group,
    // For context during callback.
    on: emptyOn$1,
    tween: emptyTween$1,
    time: timing.time,
    delay: timing.delay,
    duration: timing.duration,
    ease: timing.ease,
    timer: null,
    state: CREATED$1
  });
}
function init$1(node, id2) {
  var schedule2 = get$2(node, id2);
  if (schedule2.state > CREATED$1) throw new Error("too late; already scheduled");
  return schedule2;
}
function set$2(node, id2) {
  var schedule2 = get$2(node, id2);
  if (schedule2.state > STARTED$1) throw new Error("too late; already running");
  return schedule2;
}
function get$2(node, id2) {
  var schedule2 = node.__transition;
  if (!schedule2 || !(schedule2 = schedule2[id2])) throw new Error("transition not found");
  return schedule2;
}
function create$1(node, id2, self) {
  var schedules = node.__transition,
    tween;
  schedules[id2] = self;
  self.timer = timer$1(schedule2, 0, self.time);
  function schedule2(elapsed) {
    self.state = SCHEDULED$1;
    self.timer.restart(start2, self.delay, self.time);
    if (self.delay <= elapsed) start2(elapsed - self.delay);
  }
  function start2(elapsed) {
    var i, j, n, o;
    if (self.state !== SCHEDULED$1) return stop();
    for (i in schedules) {
      o = schedules[i];
      if (o.name !== self.name) continue;
      if (o.state === STARTED$1) return timeout$2(start2);
      if (o.state === RUNNING$1) {
        o.state = ENDED$1;
        o.timer.stop();
        o.on.call("interrupt", node, node.__data__, o.index, o.group);
        delete schedules[i];
      } else if (+i < id2) {
        o.state = ENDED$1;
        o.timer.stop();
        o.on.call("cancel", node, node.__data__, o.index, o.group);
        delete schedules[i];
      }
    }
    timeout$2(function () {
      if (self.state === STARTED$1) {
        self.state = RUNNING$1;
        self.timer.restart(tick, self.delay, self.time);
        tick(elapsed);
      }
    });
    self.state = STARTING$1;
    self.on.call("start", node, node.__data__, self.index, self.group);
    if (self.state !== STARTING$1) return;
    self.state = STARTED$1;
    tween = new Array(n = self.tween.length);
    for (i = 0, j = -1; i < n; ++i) {
      if (o = self.tween[i].value.call(node, node.__data__, self.index, self.group)) {
        tween[++j] = o;
      }
    }
    tween.length = j + 1;
  }
  function tick(elapsed) {
    var t = elapsed < self.duration ? self.ease.call(null, elapsed / self.duration) : (self.timer.restart(stop), self.state = ENDING$1, 1),
      i = -1,
      n = tween.length;
    while (++i < n) {
      tween[i].call(node, t);
    }
    if (self.state === ENDING$1) {
      self.on.call("end", node, node.__data__, self.index, self.group);
      stop();
    }
  }
  function stop() {
    self.state = ENDED$1;
    self.timer.stop();
    delete schedules[id2];
    for (var i in schedules) return;
    delete node.__transition;
  }
}
function interrupt$1(node, name) {
  var schedules = node.__transition,
    schedule2,
    active,
    empty2 = true,
    i;
  if (!schedules) return;
  name = name == null ? null : name + "";
  for (i in schedules) {
    if ((schedule2 = schedules[i]).name !== name) {
      empty2 = false;
      continue;
    }
    active = schedule2.state > STARTING$1 && schedule2.state < ENDING$1;
    schedule2.state = ENDED$1;
    schedule2.timer.stop();
    schedule2.on.call(active ? "interrupt" : "cancel", node, node.__data__, schedule2.index, schedule2.group);
    delete schedules[i];
  }
  if (empty2) delete node.__transition;
}
function selection_interrupt$1(name) {
  return this.each(function () {
    interrupt$1(this, name);
  });
}
function tweenRemove$1(id2, name) {
  var tween0, tween1;
  return function () {
    var schedule2 = set$2(this, id2),
      tween = schedule2.tween;
    if (tween !== tween0) {
      tween1 = tween0 = tween;
      for (var i = 0, n = tween1.length; i < n; ++i) {
        if (tween1[i].name === name) {
          tween1 = tween1.slice();
          tween1.splice(i, 1);
          break;
        }
      }
    }
    schedule2.tween = tween1;
  };
}
function tweenFunction$1(id2, name, value) {
  var tween0, tween1;
  if (typeof value !== "function") throw new Error();
  return function () {
    var schedule2 = set$2(this, id2),
      tween = schedule2.tween;
    if (tween !== tween0) {
      tween1 = (tween0 = tween).slice();
      for (var t = {
          name,
          value
        }, i = 0, n = tween1.length; i < n; ++i) {
        if (tween1[i].name === name) {
          tween1[i] = t;
          break;
        }
      }
      if (i === n) tween1.push(t);
    }
    schedule2.tween = tween1;
  };
}
function transition_tween$1(name, value) {
  var id2 = this._id;
  name += "";
  if (arguments.length < 2) {
    var tween = get$2(this.node(), id2).tween;
    for (var i = 0, n = tween.length, t; i < n; ++i) {
      if ((t = tween[i]).name === name) {
        return t.value;
      }
    }
    return null;
  }
  return this.each((value == null ? tweenRemove$1 : tweenFunction$1)(id2, name, value));
}
function tweenValue$1(transition, name, value) {
  var id2 = transition._id;
  transition.each(function () {
    var schedule2 = set$2(this, id2);
    (schedule2.value || (schedule2.value = {}))[name] = value.apply(this, arguments);
  });
  return function (node) {
    return get$2(node, id2).value[name];
  };
}
function interpolate$2(a, b) {
  var c;
  return (typeof b === "number" ? interpolateNumber$1 : b instanceof color$1 ? interpolateRgb$1 : (c = color$1(b)) ? (b = c, interpolateRgb$1) : interpolateString$1)(a, b);
}
function attrRemove$2(name) {
  return function () {
    this.removeAttribute(name);
  };
}
function attrRemoveNS$2(fullname) {
  return function () {
    this.removeAttributeNS(fullname.space, fullname.local);
  };
}
function attrConstant$2(name, interpolate2, value1) {
  var string00,
    string1 = value1 + "",
    interpolate0;
  return function () {
    var string0 = this.getAttribute(name);
    return string0 === string1 ? null : string0 === string00 ? interpolate0 : interpolate0 = interpolate2(string00 = string0, value1);
  };
}
function attrConstantNS$2(fullname, interpolate2, value1) {
  var string00,
    string1 = value1 + "",
    interpolate0;
  return function () {
    var string0 = this.getAttributeNS(fullname.space, fullname.local);
    return string0 === string1 ? null : string0 === string00 ? interpolate0 : interpolate0 = interpolate2(string00 = string0, value1);
  };
}
function attrFunction$2(name, interpolate2, value) {
  var string00, string10, interpolate0;
  return function () {
    var string0,
      value1 = value(this),
      string1;
    if (value1 == null) return void this.removeAttribute(name);
    string0 = this.getAttribute(name);
    string1 = value1 + "";
    return string0 === string1 ? null : string0 === string00 && string1 === string10 ? interpolate0 : (string10 = string1, interpolate0 = interpolate2(string00 = string0, value1));
  };
}
function attrFunctionNS$2(fullname, interpolate2, value) {
  var string00, string10, interpolate0;
  return function () {
    var string0,
      value1 = value(this),
      string1;
    if (value1 == null) return void this.removeAttributeNS(fullname.space, fullname.local);
    string0 = this.getAttributeNS(fullname.space, fullname.local);
    string1 = value1 + "";
    return string0 === string1 ? null : string0 === string00 && string1 === string10 ? interpolate0 : (string10 = string1, interpolate0 = interpolate2(string00 = string0, value1));
  };
}
function transition_attr$1(name, value) {
  var fullname = namespace$1(name),
    i = fullname === "transform" ? interpolateTransformSvg$1 : interpolate$2;
  return this.attrTween(name, typeof value === "function" ? (fullname.local ? attrFunctionNS$2 : attrFunction$2)(fullname, i, tweenValue$1(this, "attr." + name, value)) : value == null ? (fullname.local ? attrRemoveNS$2 : attrRemove$2)(fullname) : (fullname.local ? attrConstantNS$2 : attrConstant$2)(fullname, i, value));
}
function attrInterpolate$1(name, i) {
  return function (t) {
    this.setAttribute(name, i.call(this, t));
  };
}
function attrInterpolateNS$1(fullname, i) {
  return function (t) {
    this.setAttributeNS(fullname.space, fullname.local, i.call(this, t));
  };
}
function attrTweenNS$1(fullname, value) {
  var t0, i0;
  function tween() {
    var i = value.apply(this, arguments);
    if (i !== i0) t0 = (i0 = i) && attrInterpolateNS$1(fullname, i);
    return t0;
  }
  tween._value = value;
  return tween;
}
function attrTween$1(name, value) {
  var t0, i0;
  function tween() {
    var i = value.apply(this, arguments);
    if (i !== i0) t0 = (i0 = i) && attrInterpolate$1(name, i);
    return t0;
  }
  tween._value = value;
  return tween;
}
function transition_attrTween$1(name, value) {
  var key = "attr." + name;
  if (arguments.length < 2) return (key = this.tween(key)) && key._value;
  if (value == null) return this.tween(key, null);
  if (typeof value !== "function") throw new Error();
  var fullname = namespace$1(name);
  return this.tween(key, (fullname.local ? attrTweenNS$1 : attrTween$1)(fullname, value));
}
function delayFunction$1(id2, value) {
  return function () {
    init$1(this, id2).delay = +value.apply(this, arguments);
  };
}
function delayConstant$1(id2, value) {
  return value = +value, function () {
    init$1(this, id2).delay = value;
  };
}
function transition_delay$1(value) {
  var id2 = this._id;
  return arguments.length ? this.each((typeof value === "function" ? delayFunction$1 : delayConstant$1)(id2, value)) : get$2(this.node(), id2).delay;
}
function durationFunction$1(id2, value) {
  return function () {
    set$2(this, id2).duration = +value.apply(this, arguments);
  };
}
function durationConstant$1(id2, value) {
  return value = +value, function () {
    set$2(this, id2).duration = value;
  };
}
function transition_duration$1(value) {
  var id2 = this._id;
  return arguments.length ? this.each((typeof value === "function" ? durationFunction$1 : durationConstant$1)(id2, value)) : get$2(this.node(), id2).duration;
}
function easeConstant$1(id2, value) {
  if (typeof value !== "function") throw new Error();
  return function () {
    set$2(this, id2).ease = value;
  };
}
function transition_ease$1(value) {
  var id2 = this._id;
  return arguments.length ? this.each(easeConstant$1(id2, value)) : get$2(this.node(), id2).ease;
}
function easeVarying$1(id2, value) {
  return function () {
    var v = value.apply(this, arguments);
    if (typeof v !== "function") throw new Error();
    set$2(this, id2).ease = v;
  };
}
function transition_easeVarying$1(value) {
  if (typeof value !== "function") throw new Error();
  return this.each(easeVarying$1(this._id, value));
}
function transition_filter$1(match) {
  if (typeof match !== "function") match = matcher$1(match);
  for (var groups = this._groups, m = groups.length, subgroups = new Array(m), j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, subgroup = subgroups[j] = [], node, i = 0; i < n; ++i) {
      if ((node = group[i]) && match.call(node, node.__data__, i, group)) {
        subgroup.push(node);
      }
    }
  }
  return new Transition$1(subgroups, this._parents, this._name, this._id);
}
function transition_merge$1(transition) {
  if (transition._id !== this._id) throw new Error();
  for (var groups0 = this._groups, groups1 = transition._groups, m0 = groups0.length, m1 = groups1.length, m = Math.min(m0, m1), merges = new Array(m0), j = 0; j < m; ++j) {
    for (var group0 = groups0[j], group1 = groups1[j], n = group0.length, merge = merges[j] = new Array(n), node, i = 0; i < n; ++i) {
      if (node = group0[i] || group1[i]) {
        merge[i] = node;
      }
    }
  }
  for (; j < m0; ++j) {
    merges[j] = groups0[j];
  }
  return new Transition$1(merges, this._parents, this._name, this._id);
}
function start$1(name) {
  return (name + "").trim().split(/^|\s+/).every(function (t) {
    var i = t.indexOf(".");
    if (i >= 0) t = t.slice(0, i);
    return !t || t === "start";
  });
}
function onFunction$1(id2, name, listener) {
  var on0,
    on1,
    sit = start$1(name) ? init$1 : set$2;
  return function () {
    var schedule2 = sit(this, id2),
      on = schedule2.on;
    if (on !== on0) (on1 = (on0 = on).copy()).on(name, listener);
    schedule2.on = on1;
  };
}
function transition_on$1(name, listener) {
  var id2 = this._id;
  return arguments.length < 2 ? get$2(this.node(), id2).on.on(name) : this.each(onFunction$1(id2, name, listener));
}
function removeFunction$1(id2) {
  return function () {
    var parent = this.parentNode;
    for (var i in this.__transition) if (+i !== id2) return;
    if (parent) parent.removeChild(this);
  };
}
function transition_remove$1() {
  return this.on("end.remove", removeFunction$1(this._id));
}
function transition_select$1(select2) {
  var name = this._name,
    id2 = this._id;
  if (typeof select2 !== "function") select2 = selector$1(select2);
  for (var groups = this._groups, m = groups.length, subgroups = new Array(m), j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, subgroup = subgroups[j] = new Array(n), node, subnode, i = 0; i < n; ++i) {
      if ((node = group[i]) && (subnode = select2.call(node, node.__data__, i, group))) {
        if ("__data__" in node) subnode.__data__ = node.__data__;
        subgroup[i] = subnode;
        schedule$1(subgroup[i], name, id2, i, subgroup, get$2(node, id2));
      }
    }
  }
  return new Transition$1(subgroups, this._parents, name, id2);
}
function transition_selectAll$1(select2) {
  var name = this._name,
    id2 = this._id;
  if (typeof select2 !== "function") select2 = selectorAll$1(select2);
  for (var groups = this._groups, m = groups.length, subgroups = [], parents = [], j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, node, i = 0; i < n; ++i) {
      if (node = group[i]) {
        for (var children2 = select2.call(node, node.__data__, i, group), child, inherit2 = get$2(node, id2), k = 0, l = children2.length; k < l; ++k) {
          if (child = children2[k]) {
            schedule$1(child, name, id2, k, children2, inherit2);
          }
        }
        subgroups.push(children2);
        parents.push(node);
      }
    }
  }
  return new Transition$1(subgroups, parents, name, id2);
}
var Selection$2 = selection$1.prototype.constructor;
function transition_selection$1() {
  return new Selection$2(this._groups, this._parents);
}
function styleNull$1(name, interpolate2) {
  var string00, string10, interpolate0;
  return function () {
    var string0 = styleValue$1(this, name),
      string1 = (this.style.removeProperty(name), styleValue$1(this, name));
    return string0 === string1 ? null : string0 === string00 && string1 === string10 ? interpolate0 : interpolate0 = interpolate2(string00 = string0, string10 = string1);
  };
}
function styleRemove$2(name) {
  return function () {
    this.style.removeProperty(name);
  };
}
function styleConstant$2(name, interpolate2, value1) {
  var string00,
    string1 = value1 + "",
    interpolate0;
  return function () {
    var string0 = styleValue$1(this, name);
    return string0 === string1 ? null : string0 === string00 ? interpolate0 : interpolate0 = interpolate2(string00 = string0, value1);
  };
}
function styleFunction$2(name, interpolate2, value) {
  var string00, string10, interpolate0;
  return function () {
    var string0 = styleValue$1(this, name),
      value1 = value(this),
      string1 = value1 + "";
    if (value1 == null) string1 = value1 = (this.style.removeProperty(name), styleValue$1(this, name));
    return string0 === string1 ? null : string0 === string00 && string1 === string10 ? interpolate0 : (string10 = string1, interpolate0 = interpolate2(string00 = string0, value1));
  };
}
function styleMaybeRemove$1(id2, name) {
  var on0,
    on1,
    listener0,
    key = "style." + name,
    event = "end." + key,
    remove2;
  return function () {
    var schedule2 = set$2(this, id2),
      on = schedule2.on,
      listener = schedule2.value[key] == null ? remove2 || (remove2 = styleRemove$2(name)) : void 0;
    if (on !== on0 || listener0 !== listener) (on1 = (on0 = on).copy()).on(event, listener0 = listener);
    schedule2.on = on1;
  };
}
function transition_style$1(name, value, priority) {
  var i = (name += "") === "transform" ? interpolateTransformCss$1 : interpolate$2;
  return value == null ? this.styleTween(name, styleNull$1(name, i)).on("end.style." + name, styleRemove$2(name)) : typeof value === "function" ? this.styleTween(name, styleFunction$2(name, i, tweenValue$1(this, "style." + name, value))).each(styleMaybeRemove$1(this._id, name)) : this.styleTween(name, styleConstant$2(name, i, value), priority).on("end.style." + name, null);
}
function styleInterpolate$1(name, i, priority) {
  return function (t) {
    this.style.setProperty(name, i.call(this, t), priority);
  };
}
function styleTween$1(name, value, priority) {
  var t, i0;
  function tween() {
    var i = value.apply(this, arguments);
    if (i !== i0) t = (i0 = i) && styleInterpolate$1(name, i, priority);
    return t;
  }
  tween._value = value;
  return tween;
}
function transition_styleTween$1(name, value, priority) {
  var key = "style." + (name += "");
  if (arguments.length < 2) return (key = this.tween(key)) && key._value;
  if (value == null) return this.tween(key, null);
  if (typeof value !== "function") throw new Error();
  return this.tween(key, styleTween$1(name, value, priority == null ? "" : priority));
}
function textConstant$2(value) {
  return function () {
    this.textContent = value;
  };
}
function textFunction$2(value) {
  return function () {
    var value1 = value(this);
    this.textContent = value1 == null ? "" : value1;
  };
}
function transition_text$1(value) {
  return this.tween("text", typeof value === "function" ? textFunction$2(tweenValue$1(this, "text", value)) : textConstant$2(value == null ? "" : value + ""));
}
function textInterpolate$1(i) {
  return function (t) {
    this.textContent = i.call(this, t);
  };
}
function textTween$1(value) {
  var t0, i0;
  function tween() {
    var i = value.apply(this, arguments);
    if (i !== i0) t0 = (i0 = i) && textInterpolate$1(i);
    return t0;
  }
  tween._value = value;
  return tween;
}
function transition_textTween$1(value) {
  var key = "text";
  if (arguments.length < 1) return (key = this.tween(key)) && key._value;
  if (value == null) return this.tween(key, null);
  if (typeof value !== "function") throw new Error();
  return this.tween(key, textTween$1(value));
}
function transition_transition$1() {
  var name = this._name,
    id0 = this._id,
    id1 = newId$1();
  for (var groups = this._groups, m = groups.length, j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, node, i = 0; i < n; ++i) {
      if (node = group[i]) {
        var inherit2 = get$2(node, id0);
        schedule$1(node, name, id1, i, group, {
          time: inherit2.time + inherit2.delay + inherit2.duration,
          delay: 0,
          duration: inherit2.duration,
          ease: inherit2.ease
        });
      }
    }
  }
  return new Transition$1(groups, this._parents, name, id1);
}
function transition_end$1() {
  var on0,
    on1,
    that = this,
    id2 = that._id,
    size = that.size();
  return new Promise(function (resolve, reject) {
    var cancel = {
        value: reject
      },
      end = {
        value: function value() {
          if (--size === 0) resolve();
        }
      };
    that.each(function () {
      var schedule2 = set$2(this, id2),
        on = schedule2.on;
      if (on !== on0) {
        on1 = (on0 = on).copy();
        on1._.cancel.push(cancel);
        on1._.interrupt.push(cancel);
        on1._.end.push(end);
      }
      schedule2.on = on1;
    });
    if (size === 0) resolve();
  });
}
var id$1 = 0;
function Transition$1(groups, parents, name, id2) {
  this._groups = groups;
  this._parents = parents;
  this._name = name;
  this._id = id2;
}
function newId$1() {
  return ++id$1;
}
var selection_prototype$1 = selection$1.prototype;
Transition$1.prototype = {
  constructor: Transition$1,
  select: transition_select$1,
  selectAll: transition_selectAll$1,
  selectChild: selection_prototype$1.selectChild,
  selectChildren: selection_prototype$1.selectChildren,
  filter: transition_filter$1,
  merge: transition_merge$1,
  selection: transition_selection$1,
  transition: transition_transition$1,
  call: selection_prototype$1.call,
  nodes: selection_prototype$1.nodes,
  node: selection_prototype$1.node,
  size: selection_prototype$1.size,
  empty: selection_prototype$1.empty,
  each: selection_prototype$1.each,
  on: transition_on$1,
  attr: transition_attr$1,
  attrTween: transition_attrTween$1,
  style: transition_style$1,
  styleTween: transition_styleTween$1,
  text: transition_text$1,
  textTween: transition_textTween$1,
  remove: transition_remove$1,
  tween: transition_tween$1,
  delay: transition_delay$1,
  duration: transition_duration$1,
  ease: transition_ease$1,
  easeVarying: transition_easeVarying$1,
  end: transition_end$1,
  [Symbol.iterator]: selection_prototype$1[Symbol.iterator]
};
function cubicInOut$1(t) {
  return ((t *= 2) <= 1 ? t * t * t : (t -= 2) * t * t + 2) / 2;
}
var defaultTiming$1 = {
  time: null,
  // Set on use.
  delay: 0,
  duration: 250,
  ease: cubicInOut$1
};
function inherit$1(node, id2) {
  var timing;
  while (!(timing = node.__transition) || !(timing = timing[id2])) {
    if (!(node = node.parentNode)) {
      throw new Error("transition " + id2 + " not found");
    }
  }
  return timing;
}
function selection_transition$1(name) {
  var id2, timing;
  if (name instanceof Transition$1) {
    id2 = name._id, name = name._name;
  } else {
    id2 = newId$1(), (timing = defaultTiming$1).time = now$1(), name = name == null ? null : name + "";
  }
  for (var groups = this._groups, m = groups.length, j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, node, i = 0; i < n; ++i) {
      if (node = group[i]) {
        schedule$1(node, name, id2, i, group, timing || inherit$1(node, id2));
      }
    }
  }
  return new Transition$1(groups, this._parents, name, id2);
}
selection$1.prototype.interrupt = selection_interrupt$1;
selection$1.prototype.transition = selection_transition$1;
const constant$4 = x => () => x;
function ZoomEvent$1(type, _ref6) {
  let sourceEvent2 = _ref6.sourceEvent,
    target = _ref6.target,
    transform = _ref6.transform,
    dispatch2 = _ref6.dispatch;
  Object.defineProperties(this, {
    type: {
      value: type,
      enumerable: true,
      configurable: true
    },
    sourceEvent: {
      value: sourceEvent2,
      enumerable: true,
      configurable: true
    },
    target: {
      value: target,
      enumerable: true,
      configurable: true
    },
    transform: {
      value: transform,
      enumerable: true,
      configurable: true
    },
    _: {
      value: dispatch2
    }
  });
}
function Transform$1(k, x, y) {
  this.k = k;
  this.x = x;
  this.y = y;
}
Transform$1.prototype = {
  constructor: Transform$1,
  scale: function scale(k) {
    return k === 1 ? this : new Transform$1(this.k * k, this.x, this.y);
  },
  translate: function translate(x, y) {
    return x === 0 & y === 0 ? this : new Transform$1(this.k, this.x + this.k * x, this.y + this.k * y);
  },
  apply: function apply(point) {
    return [point[0] * this.k + this.x, point[1] * this.k + this.y];
  },
  applyX: function applyX(x) {
    return x * this.k + this.x;
  },
  applyY: function applyY(y) {
    return y * this.k + this.y;
  },
  invert: function invert(location) {
    return [(location[0] - this.x) / this.k, (location[1] - this.y) / this.k];
  },
  invertX: function invertX(x) {
    return (x - this.x) / this.k;
  },
  invertY: function invertY(y) {
    return (y - this.y) / this.k;
  },
  rescaleX: function rescaleX(x) {
    return x.copy().domain(x.range().map(this.invertX, this).map(x.invert, x));
  },
  rescaleY: function rescaleY(y) {
    return y.copy().domain(y.range().map(this.invertY, this).map(y.invert, y));
  },
  toString: function toString() {
    return "translate(" + this.x + "," + this.y + ") scale(" + this.k + ")";
  }
};
var identity$2 = new Transform$1(1, 0, 0);
Transform$1.prototype;
function nopropagation$2(event) {
  event.stopImmediatePropagation();
}
function noevent$2(event) {
  event.preventDefault();
  event.stopImmediatePropagation();
}
function defaultFilter$2(event) {
  return (!event.ctrlKey || event.type === "wheel") && !event.button;
}
function defaultExtent$1() {
  var e = this;
  if (e instanceof SVGElement) {
    e = e.ownerSVGElement || e;
    if (e.hasAttribute("viewBox")) {
      e = e.viewBox.baseVal;
      return [[e.x, e.y], [e.x + e.width, e.y + e.height]];
    }
    return [[0, 0], [e.width.baseVal.value, e.height.baseVal.value]];
  }
  return [[0, 0], [e.clientWidth, e.clientHeight]];
}
function defaultTransform$1() {
  return this.__zoom || identity$2;
}
function defaultWheelDelta$1(event) {
  return -event.deltaY * (event.deltaMode === 1 ? 0.05 : event.deltaMode ? 1 : 2e-3) * (event.ctrlKey ? 10 : 1);
}
function defaultTouchable$2() {
  return navigator.maxTouchPoints || "ontouchstart" in this;
}
function defaultConstrain$1(transform, extent, translateExtent) {
  var dx0 = transform.invertX(extent[0][0]) - translateExtent[0][0],
    dx1 = transform.invertX(extent[1][0]) - translateExtent[1][0],
    dy0 = transform.invertY(extent[0][1]) - translateExtent[0][1],
    dy1 = transform.invertY(extent[1][1]) - translateExtent[1][1];
  return transform.translate(dx1 > dx0 ? (dx0 + dx1) / 2 : Math.min(0, dx0) || Math.max(0, dx1), dy1 > dy0 ? (dy0 + dy1) / 2 : Math.min(0, dy0) || Math.max(0, dy1));
}
function zoom$1() {
  var filter2 = defaultFilter$2,
    extent = defaultExtent$1,
    constrain = defaultConstrain$1,
    wheelDelta2 = defaultWheelDelta$1,
    touchable = defaultTouchable$2,
    scaleExtent = [0, Infinity],
    translateExtent = [[-Infinity, -Infinity], [Infinity, Infinity]],
    duration = 250,
    interpolate2 = interpolateZoom$1,
    listeners = dispatch$1("start", "zoom", "end"),
    touchstarting,
    touchfirst,
    touchending,
    touchDelay = 500,
    wheelDelay = 150,
    clickDistance2 = 0,
    tapDistance = 10;
  function zoom2(selection2) {
    selection2.property("__zoom", defaultTransform$1).on("wheel.zoom", wheeled, {
      passive: false
    }).on("mousedown.zoom", mousedowned).on("dblclick.zoom", dblclicked).filter(touchable).on("touchstart.zoom", touchstarted).on("touchmove.zoom", touchmoved).on("touchend.zoom touchcancel.zoom", touchended).style("-webkit-tap-highlight-color", "rgba(0,0,0,0)");
  }
  zoom2.transform = function (collection, transform, point, event) {
    var selection2 = collection.selection ? collection.selection() : collection;
    selection2.property("__zoom", defaultTransform$1);
    if (collection !== selection2) {
      schedule2(collection, transform, point, event);
    } else {
      selection2.interrupt().each(function () {
        gesture(this, arguments).event(event).start().zoom(null, typeof transform === "function" ? transform.apply(this, arguments) : transform).end();
      });
    }
  };
  zoom2.scaleBy = function (selection2, k, p, event) {
    zoom2.scaleTo(selection2, function () {
      var k0 = this.__zoom.k,
        k1 = typeof k === "function" ? k.apply(this, arguments) : k;
      return k0 * k1;
    }, p, event);
  };
  zoom2.scaleTo = function (selection2, k, p, event) {
    zoom2.transform(selection2, function () {
      var e = extent.apply(this, arguments),
        t0 = this.__zoom,
        p0 = p == null ? centroid(e) : typeof p === "function" ? p.apply(this, arguments) : p,
        p1 = t0.invert(p0),
        k1 = typeof k === "function" ? k.apply(this, arguments) : k;
      return constrain(translate(scale(t0, k1), p0, p1), e, translateExtent);
    }, p, event);
  };
  zoom2.translateBy = function (selection2, x, y, event) {
    zoom2.transform(selection2, function () {
      return constrain(this.__zoom.translate(typeof x === "function" ? x.apply(this, arguments) : x, typeof y === "function" ? y.apply(this, arguments) : y), extent.apply(this, arguments), translateExtent);
    }, null, event);
  };
  zoom2.translateTo = function (selection2, x, y, p, event) {
    zoom2.transform(selection2, function () {
      var e = extent.apply(this, arguments),
        t = this.__zoom,
        p0 = p == null ? centroid(e) : typeof p === "function" ? p.apply(this, arguments) : p;
      return constrain(identity$2.translate(p0[0], p0[1]).scale(t.k).translate(typeof x === "function" ? -x.apply(this, arguments) : -x, typeof y === "function" ? -y.apply(this, arguments) : -y), e, translateExtent);
    }, p, event);
  };
  function scale(transform, k) {
    k = Math.max(scaleExtent[0], Math.min(scaleExtent[1], k));
    return k === transform.k ? transform : new Transform$1(k, transform.x, transform.y);
  }
  function translate(transform, p0, p1) {
    var x = p0[0] - p1[0] * transform.k,
      y = p0[1] - p1[1] * transform.k;
    return x === transform.x && y === transform.y ? transform : new Transform$1(transform.k, x, y);
  }
  function centroid(extent2) {
    return [(+extent2[0][0] + +extent2[1][0]) / 2, (+extent2[0][1] + +extent2[1][1]) / 2];
  }
  function schedule2(transition, transform, point, event) {
    transition.on("start.zoom", function () {
      gesture(this, arguments).event(event).start();
    }).on("interrupt.zoom end.zoom", function () {
      gesture(this, arguments).event(event).end();
    }).tween("zoom", function () {
      var that = this,
        args = arguments,
        g = gesture(that, args).event(event),
        e = extent.apply(that, args),
        p = point == null ? centroid(e) : typeof point === "function" ? point.apply(that, args) : point,
        w = Math.max(e[1][0] - e[0][0], e[1][1] - e[0][1]),
        a = that.__zoom,
        b = typeof transform === "function" ? transform.apply(that, args) : transform,
        i = interpolate2(a.invert(p).concat(w / a.k), b.invert(p).concat(w / b.k));
      return function (t) {
        if (t === 1) t = b;else {
          var l = i(t),
            k = w / l[2];
          t = new Transform$1(k, p[0] - l[0] * k, p[1] - l[1] * k);
        }
        g.zoom(null, t);
      };
    });
  }
  function gesture(that, args, clean) {
    return !clean && that.__zooming || new Gesture(that, args);
  }
  function Gesture(that, args) {
    this.that = that;
    this.args = args;
    this.active = 0;
    this.sourceEvent = null;
    this.extent = extent.apply(that, args);
    this.taps = 0;
  }
  Gesture.prototype = {
    event: function event(_event) {
      if (_event) this.sourceEvent = _event;
      return this;
    },
    start: function start() {
      if (++this.active === 1) {
        this.that.__zooming = this;
        this.emit("start");
      }
      return this;
    },
    zoom: function zoom(key, transform) {
      if (this.mouse && key !== "mouse") this.mouse[1] = transform.invert(this.mouse[0]);
      if (this.touch0 && key !== "touch") this.touch0[1] = transform.invert(this.touch0[0]);
      if (this.touch1 && key !== "touch") this.touch1[1] = transform.invert(this.touch1[0]);
      this.that.__zoom = transform;
      this.emit("zoom");
      return this;
    },
    end: function end() {
      if (--this.active === 0) {
        delete this.that.__zooming;
        this.emit("end");
      }
      return this;
    },
    emit: function emit(type) {
      var d = select$1(this.that).datum();
      listeners.call(type, this.that, new ZoomEvent$1(type, {
        sourceEvent: this.sourceEvent,
        target: zoom2,
        transform: this.that.__zoom,
        dispatch: listeners
      }), d);
    }
  };
  function wheeled(event) {
    for (var _len3 = arguments.length, args = new Array(_len3 > 1 ? _len3 - 1 : 0), _key3 = 1; _key3 < _len3; _key3++) {
      args[_key3 - 1] = arguments[_key3];
    }
    if (!filter2.apply(this, arguments)) return;
    var g = gesture(this, args).event(event),
      t = this.__zoom,
      k = Math.max(scaleExtent[0], Math.min(scaleExtent[1], t.k * Math.pow(2, wheelDelta2.apply(this, arguments)))),
      p = pointer$1(event);
    if (g.wheel) {
      if (g.mouse[0][0] !== p[0] || g.mouse[0][1] !== p[1]) {
        g.mouse[1] = t.invert(g.mouse[0] = p);
      }
      clearTimeout(g.wheel);
    } else if (t.k === k) return;else {
      g.mouse = [p, t.invert(p)];
      interrupt$1(this);
      g.start();
    }
    noevent$2(event);
    g.wheel = setTimeout(wheelidled, wheelDelay);
    g.zoom("mouse", constrain(translate(scale(t, k), g.mouse[0], g.mouse[1]), g.extent, translateExtent));
    function wheelidled() {
      g.wheel = null;
      g.end();
    }
  }
  function mousedowned(event) {
    for (var _len4 = arguments.length, args = new Array(_len4 > 1 ? _len4 - 1 : 0), _key4 = 1; _key4 < _len4; _key4++) {
      args[_key4 - 1] = arguments[_key4];
    }
    if (touchending || !filter2.apply(this, arguments)) return;
    var currentTarget = event.currentTarget,
      g = gesture(this, args, true).event(event),
      v = select$1(event.view).on("mousemove.zoom", mousemoved, true).on("mouseup.zoom", mouseupped, true),
      p = pointer$1(event, currentTarget),
      x0 = event.clientX,
      y0 = event.clientY;
    dragDisable$1(event.view);
    nopropagation$2(event);
    g.mouse = [p, this.__zoom.invert(p)];
    interrupt$1(this);
    g.start();
    function mousemoved(event2) {
      noevent$2(event2);
      if (!g.moved) {
        var dx = event2.clientX - x0,
          dy = event2.clientY - y0;
        g.moved = dx * dx + dy * dy > clickDistance2;
      }
      g.event(event2).zoom("mouse", constrain(translate(g.that.__zoom, g.mouse[0] = pointer$1(event2, currentTarget), g.mouse[1]), g.extent, translateExtent));
    }
    function mouseupped(event2) {
      v.on("mousemove.zoom mouseup.zoom", null);
      yesdrag$1(event2.view, g.moved);
      noevent$2(event2);
      g.event(event2).end();
    }
  }
  function dblclicked(event) {
    for (var _len5 = arguments.length, args = new Array(_len5 > 1 ? _len5 - 1 : 0), _key5 = 1; _key5 < _len5; _key5++) {
      args[_key5 - 1] = arguments[_key5];
    }
    if (!filter2.apply(this, arguments)) return;
    var t0 = this.__zoom,
      p0 = pointer$1(event.changedTouches ? event.changedTouches[0] : event, this),
      p1 = t0.invert(p0),
      k1 = t0.k * (event.shiftKey ? 0.5 : 2),
      t1 = constrain(translate(scale(t0, k1), p0, p1), extent.apply(this, args), translateExtent);
    noevent$2(event);
    if (duration > 0) select$1(this).transition().duration(duration).call(schedule2, t1, p0, event);else select$1(this).call(zoom2.transform, t1, p0, event);
  }
  function touchstarted(event) {
    for (var _len6 = arguments.length, args = new Array(_len6 > 1 ? _len6 - 1 : 0), _key6 = 1; _key6 < _len6; _key6++) {
      args[_key6 - 1] = arguments[_key6];
    }
    if (!filter2.apply(this, arguments)) return;
    var touches = event.touches,
      n = touches.length,
      g = gesture(this, args, event.changedTouches.length === n).event(event),
      started,
      i,
      t,
      p;
    nopropagation$2(event);
    for (i = 0; i < n; ++i) {
      t = touches[i], p = pointer$1(t, this);
      p = [p, this.__zoom.invert(p), t.identifier];
      if (!g.touch0) g.touch0 = p, started = true, g.taps = 1 + !!touchstarting;else if (!g.touch1 && g.touch0[2] !== p[2]) g.touch1 = p, g.taps = 0;
    }
    if (touchstarting) touchstarting = clearTimeout(touchstarting);
    if (started) {
      if (g.taps < 2) touchfirst = p[0], touchstarting = setTimeout(function () {
        touchstarting = null;
      }, touchDelay);
      interrupt$1(this);
      g.start();
    }
  }
  function touchmoved(event) {
    if (!this.__zooming) return;
    for (var _len7 = arguments.length, args = new Array(_len7 > 1 ? _len7 - 1 : 0), _key7 = 1; _key7 < _len7; _key7++) {
      args[_key7 - 1] = arguments[_key7];
    }
    var g = gesture(this, args).event(event),
      touches = event.changedTouches,
      n = touches.length,
      i,
      t,
      p,
      l;
    noevent$2(event);
    for (i = 0; i < n; ++i) {
      t = touches[i], p = pointer$1(t, this);
      if (g.touch0 && g.touch0[2] === t.identifier) g.touch0[0] = p;else if (g.touch1 && g.touch1[2] === t.identifier) g.touch1[0] = p;
    }
    t = g.that.__zoom;
    if (g.touch1) {
      var p0 = g.touch0[0],
        l0 = g.touch0[1],
        p1 = g.touch1[0],
        l1 = g.touch1[1],
        dp = (dp = p1[0] - p0[0]) * dp + (dp = p1[1] - p0[1]) * dp,
        dl = (dl = l1[0] - l0[0]) * dl + (dl = l1[1] - l0[1]) * dl;
      t = scale(t, Math.sqrt(dp / dl));
      p = [(p0[0] + p1[0]) / 2, (p0[1] + p1[1]) / 2];
      l = [(l0[0] + l1[0]) / 2, (l0[1] + l1[1]) / 2];
    } else if (g.touch0) p = g.touch0[0], l = g.touch0[1];else return;
    g.zoom("touch", constrain(translate(t, p, l), g.extent, translateExtent));
  }
  function touchended(event) {
    for (var _len8 = arguments.length, args = new Array(_len8 > 1 ? _len8 - 1 : 0), _key8 = 1; _key8 < _len8; _key8++) {
      args[_key8 - 1] = arguments[_key8];
    }
    if (!this.__zooming) return;
    var g = gesture(this, args).event(event),
      touches = event.changedTouches,
      n = touches.length,
      i,
      t;
    nopropagation$2(event);
    if (touchending) clearTimeout(touchending);
    touchending = setTimeout(function () {
      touchending = null;
    }, touchDelay);
    for (i = 0; i < n; ++i) {
      t = touches[i];
      if (g.touch0 && g.touch0[2] === t.identifier) delete g.touch0;else if (g.touch1 && g.touch1[2] === t.identifier) delete g.touch1;
    }
    if (g.touch1 && !g.touch0) g.touch0 = g.touch1, delete g.touch1;
    if (g.touch0) g.touch0[1] = this.__zoom.invert(g.touch0[0]);else {
      g.end();
      if (g.taps === 2) {
        t = pointer$1(t, this);
        if (Math.hypot(touchfirst[0] - t[0], touchfirst[1] - t[1]) < tapDistance) {
          var p = select$1(this).on("dblclick.zoom");
          if (p) p.apply(this, arguments);
        }
      }
    }
  }
  zoom2.wheelDelta = function (_) {
    return arguments.length ? (wheelDelta2 = typeof _ === "function" ? _ : constant$4(+_), zoom2) : wheelDelta2;
  };
  zoom2.filter = function (_) {
    return arguments.length ? (filter2 = typeof _ === "function" ? _ : constant$4(!!_), zoom2) : filter2;
  };
  zoom2.touchable = function (_) {
    return arguments.length ? (touchable = typeof _ === "function" ? _ : constant$4(!!_), zoom2) : touchable;
  };
  zoom2.extent = function (_) {
    return arguments.length ? (extent = typeof _ === "function" ? _ : constant$4([[+_[0][0], +_[0][1]], [+_[1][0], +_[1][1]]]), zoom2) : extent;
  };
  zoom2.scaleExtent = function (_) {
    return arguments.length ? (scaleExtent[0] = +_[0], scaleExtent[1] = +_[1], zoom2) : [scaleExtent[0], scaleExtent[1]];
  };
  zoom2.translateExtent = function (_) {
    return arguments.length ? (translateExtent[0][0] = +_[0][0], translateExtent[1][0] = +_[1][0], translateExtent[0][1] = +_[0][1], translateExtent[1][1] = +_[1][1], zoom2) : [[translateExtent[0][0], translateExtent[0][1]], [translateExtent[1][0], translateExtent[1][1]]];
  };
  zoom2.constrain = function (_) {
    return arguments.length ? (constrain = _, zoom2) : constrain;
  };
  zoom2.duration = function (_) {
    return arguments.length ? (duration = +_, zoom2) : duration;
  };
  zoom2.interpolate = function (_) {
    return arguments.length ? (interpolate2 = _, zoom2) : interpolate2;
  };
  zoom2.on = function () {
    var value = listeners.on.apply(listeners, arguments);
    return value === listeners ? zoom2 : value;
  };
  zoom2.clickDistance = function (_) {
    return arguments.length ? (clickDistance2 = (_ = +_) * _, zoom2) : Math.sqrt(clickDistance2);
  };
  zoom2.tapDistance = function (_) {
    return arguments.length ? (tapDistance = +_, zoom2) : tapDistance;
  };
  return zoom2;
}
var Position = /* @__PURE__ */(Position2 => {
  Position2["Left"] = "left";
  Position2["Top"] = "top";
  Position2["Right"] = "right";
  Position2["Bottom"] = "bottom";
  return Position2;
})(Position || {});
var SelectionMode = /* @__PURE__ */(SelectionMode2 => {
  SelectionMode2["Partial"] = "partial";
  SelectionMode2["Full"] = "full";
  return SelectionMode2;
})(SelectionMode || {});
var ConnectionLineType = /* @__PURE__ */(ConnectionLineType2 => {
  ConnectionLineType2["Bezier"] = "default";
  ConnectionLineType2["SimpleBezier"] = "simple-bezier";
  ConnectionLineType2["Straight"] = "straight";
  ConnectionLineType2["Step"] = "step";
  ConnectionLineType2["SmoothStep"] = "smoothstep";
  return ConnectionLineType2;
})(ConnectionLineType || {});
var ConnectionMode = /* @__PURE__ */(ConnectionMode2 => {
  ConnectionMode2["Strict"] = "strict";
  ConnectionMode2["Loose"] = "loose";
  return ConnectionMode2;
})(ConnectionMode || {});
var MarkerType = /* @__PURE__ */(MarkerType2 => {
  MarkerType2["Arrow"] = "arrow";
  MarkerType2["ArrowClosed"] = "arrowclosed";
  return MarkerType2;
})(MarkerType || {});
var PanOnScrollMode = /* @__PURE__ */(PanOnScrollMode2 => {
  PanOnScrollMode2["Free"] = "free";
  PanOnScrollMode2["Vertical"] = "vertical";
  PanOnScrollMode2["Horizontal"] = "horizontal";
  return PanOnScrollMode2;
})(PanOnScrollMode || {});
const inputTags = ["INPUT", "SELECT", "TEXTAREA"];
const defaultDoc = typeof document !== "undefined" ? document : null;
function isInputDOMNode(event) {
  var _a, _b;
  const target = ((_b = (_a = event.composedPath) == null ? void 0 : _a.call(event)) == null ? void 0 : _b[0]) || event.target;
  const hasAttribute = typeof (target == null ? void 0 : target.hasAttribute) === "function" ? target.hasAttribute("contenteditable") : false;
  const closest = typeof (target == null ? void 0 : target.closest) === "function" ? target.closest(".nokey") : null;
  return inputTags.includes(target == null ? void 0 : target.nodeName) || hasAttribute || !!closest;
}
function wasModifierPressed(event) {
  return event.ctrlKey || event.metaKey || event.shiftKey || event.altKey;
}
function isKeyMatch(pressedKey, keyToMatch, pressedKeys, isKeyUp) {
  const keyCombination = keyToMatch.replace("+", "\n").replace("\n\n", "\n+").split("\n").map(k => k.trim().toLowerCase());
  if (keyCombination.length === 1) {
    return pressedKey.toLowerCase() === keyToMatch.toLowerCase();
  }
  if (!isKeyUp) {
    pressedKeys.add(pressedKey.toLowerCase());
  }
  const isMatch = keyCombination.every((key, index) => pressedKeys.has(key) && Array.from(pressedKeys.values())[index] === keyCombination[index]);
  if (isKeyUp) {
    pressedKeys.delete(pressedKey.toLowerCase());
  }
  return isMatch;
}
function createKeyPredicate(keyFilter, pressedKeys) {
  return event => {
    if (!event.code && !event.key) {
      return false;
    }
    const keyOrCode = useKeyOrCode(event.code, keyFilter);
    if (Array.isArray(keyFilter)) {
      return keyFilter.some(key => isKeyMatch(event[keyOrCode], key, pressedKeys, event.type === "keyup"));
    }
    return isKeyMatch(event[keyOrCode], keyFilter, pressedKeys, event.type === "keyup");
  };
}
function useKeyOrCode(code, keysToWatch) {
  return keysToWatch.includes(code) ? "code" : "key";
}
function useKeyPress(keyFilter, options) {
  const target = computed(() => {
    var _toValue$;
    return (_toValue$ = toValue$1(options == null ? void 0 : options.target)) != null ? _toValue$ : defaultDoc;
  });
  const isPressed = shallowRef(toValue$1(keyFilter) === true);
  let modifierPressed = false;
  const pressedKeys = /* @__PURE__ */new Set();
  let currentFilter = createKeyFilterFn(toValue$1(keyFilter));
  watch(() => toValue$1(keyFilter), (nextKeyFilter, previousKeyFilter) => {
    if (typeof previousKeyFilter === "boolean" && typeof nextKeyFilter !== "boolean") {
      reset();
    }
    currentFilter = createKeyFilterFn(nextKeyFilter);
  }, {
    immediate: true
  });
  useEventListener(["blur", "contextmenu"], reset);
  onKeyStroke(function () {
    return currentFilter(...arguments);
  }, e => {
    var _toValue$2, _toValue$3;
    var _a, _b;
    const actInsideInputWithModifier = (_toValue$2 = toValue$1(options == null ? void 0 : options.actInsideInputWithModifier)) != null ? _toValue$2 : true;
    const preventDefault = (_toValue$3 = toValue$1(options == null ? void 0 : options.preventDefault)) != null ? _toValue$3 : false;
    modifierPressed = wasModifierPressed(e);
    const preventAction = (!modifierPressed || modifierPressed && !actInsideInputWithModifier) && isInputDOMNode(e);
    if (preventAction) {
      return;
    }
    const target2 = ((_b = (_a = e.composedPath) == null ? void 0 : _a.call(e)) == null ? void 0 : _b[0]) || e.target;
    const isInteractiveElement = (target2 == null ? void 0 : target2.nodeName) === "BUTTON" || (target2 == null ? void 0 : target2.nodeName) === "A";
    if (!preventDefault && (modifierPressed || !isInteractiveElement)) {
      e.preventDefault();
    }
    isPressed.value = true;
  }, {
    eventName: "keydown",
    target
  });
  onKeyStroke(function () {
    return currentFilter(...arguments);
  }, e => {
    var _toValue$4;
    const actInsideInputWithModifier = (_toValue$4 = toValue$1(options == null ? void 0 : options.actInsideInputWithModifier)) != null ? _toValue$4 : true;
    if (isPressed.value) {
      const preventAction = (!modifierPressed || modifierPressed && !actInsideInputWithModifier) && isInputDOMNode(e);
      if (preventAction) {
        return;
      }
      modifierPressed = false;
      isPressed.value = false;
    }
  }, {
    eventName: "keyup",
    target
  });
  function reset() {
    modifierPressed = false;
    pressedKeys.clear();
    isPressed.value = toValue$1(keyFilter) === true;
  }
  function createKeyFilterFn(keyFilter2) {
    if (keyFilter2 === null) {
      reset();
      return () => false;
    }
    if (typeof keyFilter2 === "boolean") {
      reset();
      isPressed.value = keyFilter2;
      return () => false;
    }
    if (Array.isArray(keyFilter2) || typeof keyFilter2 === "string") {
      return createKeyPredicate(keyFilter2, pressedKeys);
    }
    return keyFilter2;
  }
  return isPressed;
}
const ARIA_NODE_DESC_KEY = "vue-flow__node-desc";
const ARIA_EDGE_DESC_KEY = "vue-flow__edge-desc";
const ARIA_LIVE_MESSAGE = "vue-flow__aria-live";
const elementSelectionKeys = ["Enter", " ", "Escape"];
const arrowKeyDiffs = {
  ArrowUp: {
    x: 0,
    y: -1
  },
  ArrowDown: {
    x: 0,
    y: 1
  },
  ArrowLeft: {
    x: -1,
    y: 0
  },
  ArrowRight: {
    x: 1,
    y: 0
  }
};
function nodeToRect(node) {
  return _extends({}, node.computedPosition || {
    x: 0,
    y: 0
  }, {
    width: node.dimensions.width || 0,
    height: node.dimensions.height || 0
  });
}
function getOverlappingArea(rectA, rectB) {
  const xOverlap = Math.max(0, Math.min(rectA.x + rectA.width, rectB.x + rectB.width) - Math.max(rectA.x, rectB.x));
  const yOverlap = Math.max(0, Math.min(rectA.y + rectA.height, rectB.y + rectB.height) - Math.max(rectA.y, rectB.y));
  return Math.ceil(xOverlap * yOverlap);
}
function getDimensions(node) {
  return {
    width: node.offsetWidth,
    height: node.offsetHeight
  };
}
function clamp(val, min, max) {
  if (min === void 0) {
    min = 0;
  }
  if (max === void 0) {
    max = 1;
  }
  return Math.min(Math.max(val, min), max);
}
function clampPosition(position, extent) {
  return {
    x: clamp(position.x, extent[0][0], extent[1][0]),
    y: clamp(position.y, extent[0][1], extent[1][1])
  };
}
function getHostForElement(element) {
  const doc = element.getRootNode();
  if ("elementFromPoint" in doc) {
    return doc;
  }
  return window.document;
}
function isEdge(element) {
  return element && typeof element === "object" && "id" in element && "source" in element && "target" in element;
}
function isNode(element) {
  return element && typeof element === "object" && "id" in element && "position" in element && !isEdge(element);
}
function isGraphNode(element) {
  return isNode(element) && "computedPosition" in element;
}
function isNumeric(n) {
  return !Number.isNaN(n) && Number.isFinite(n);
}
function isRect(obj) {
  return isNumeric(obj.width) && isNumeric(obj.height) && isNumeric(obj.x) && isNumeric(obj.y);
}
function parseNode(node, existingNode, parentNode) {
  var _node$type;
  const initialState = {
    id: node.id.toString(),
    type: (_node$type = node.type) != null ? _node$type : "default",
    dimensions: markRaw({
      width: 0,
      height: 0
    }),
    computedPosition: markRaw(_extends({
      z: 0
    }, node.position)),
    // todo: shouldn't be defined initially, as we want to use handleBounds to check if a node was actually initialized or not
    handleBounds: {
      source: [],
      target: []
    },
    draggable: void 0,
    selectable: void 0,
    connectable: void 0,
    focusable: void 0,
    selected: false,
    dragging: false,
    resizing: false,
    initialized: false,
    isParent: false,
    position: {
      x: 0,
      y: 0
    },
    data: isDef(node.data) ? node.data : {},
    events: markRaw(isDef(node.events) ? node.events : {})
  };
  return Object.assign(existingNode != null ? existingNode : initialState, node, {
    id: node.id.toString(),
    parentNode
  });
}
function parseEdge(edge, existingEdge, defaultEdgeOptions) {
  var _ref7, _edge$type, _edge$updatable, _edge$selectable, _edge$focusable, _edge$label, _edge$interactionWidt;
  var _a, _b;
  const initialState = _extends({
    id: edge.id.toString(),
    type: (_ref7 = (_edge$type = edge.type) != null ? _edge$type : existingEdge == null ? void 0 : existingEdge.type) != null ? _ref7 : "default",
    source: edge.source.toString(),
    target: edge.target.toString(),
    sourceHandle: (_a = edge.sourceHandle) == null ? void 0 : _a.toString(),
    targetHandle: (_b = edge.targetHandle) == null ? void 0 : _b.toString(),
    updatable: (_edge$updatable = edge.updatable) != null ? _edge$updatable : defaultEdgeOptions == null ? void 0 : defaultEdgeOptions.updatable,
    selectable: (_edge$selectable = edge.selectable) != null ? _edge$selectable : defaultEdgeOptions == null ? void 0 : defaultEdgeOptions.selectable,
    focusable: (_edge$focusable = edge.focusable) != null ? _edge$focusable : defaultEdgeOptions == null ? void 0 : defaultEdgeOptions.focusable,
    data: isDef(edge.data) ? edge.data : {},
    events: markRaw(isDef(edge.events) ? edge.events : {}),
    label: (_edge$label = edge.label) != null ? _edge$label : "",
    interactionWidth: (_edge$interactionWidt = edge.interactionWidth) != null ? _edge$interactionWidt : defaultEdgeOptions == null ? void 0 : defaultEdgeOptions.interactionWidth
  }, defaultEdgeOptions != null ? defaultEdgeOptions : {});
  return Object.assign(existingEdge != null ? existingEdge : initialState, edge, {
    id: edge.id.toString()
  });
}
function getConnectedElements(nodeOrId, nodes, edges, dir) {
  const id2 = typeof nodeOrId === "string" ? nodeOrId : nodeOrId.id;
  const connectedIds = /* @__PURE__ */new Set();
  const origin = dir === "source" ? "target" : "source";
  for (const edge of edges) {
    if (edge[origin] === id2) {
      connectedIds.add(edge[dir]);
    }
  }
  return nodes.filter(n => connectedIds.has(n.id));
}
function getOutgoers() {
  for (var _len9 = arguments.length, args = new Array(_len9), _key9 = 0; _key9 < _len9; _key9++) {
    args[_key9] = arguments[_key9];
  }
  if (args.length === 3) {
    const nodeOrId2 = args[0],
      nodes = args[1],
      edges = args[2];
    return getConnectedElements(nodeOrId2, nodes, edges, "target");
  }
  const nodeOrId = args[0],
    elements = args[1];
  const nodeId = typeof nodeOrId === "string" ? nodeOrId : nodeOrId.id;
  const outgoers = elements.filter(el => isEdge(el) && el.source === nodeId);
  return outgoers.map(edge => elements.find(el => isNode(el) && el.id === edge.target));
}
function getIncomers() {
  for (var _len0 = arguments.length, args = new Array(_len0), _key0 = 0; _key0 < _len0; _key0++) {
    args[_key0] = arguments[_key0];
  }
  if (args.length === 3) {
    const nodeOrId2 = args[0],
      nodes = args[1],
      edges = args[2];
    return getConnectedElements(nodeOrId2, nodes, edges, "source");
  }
  const nodeOrId = args[0],
    elements = args[1];
  const nodeId = typeof nodeOrId === "string" ? nodeOrId : nodeOrId.id;
  const incomers = elements.filter(el => isEdge(el) && el.target === nodeId);
  return incomers.map(edge => elements.find(el => isNode(el) && el.id === edge.source));
}
function getEdgeId(_ref8) {
  let source = _ref8.source,
    sourceHandle = _ref8.sourceHandle,
    target = _ref8.target,
    targetHandle = _ref8.targetHandle;
  return "vueflow__edge-" + source + (sourceHandle != null ? sourceHandle : "") + "-" + target + (targetHandle != null ? targetHandle : "");
}
function connectionExists(edge, elements) {
  return elements.some(el => isEdge(el) && el.source === edge.source && el.target === edge.target && (el.sourceHandle === edge.sourceHandle || !el.sourceHandle && !edge.sourceHandle) && (el.targetHandle === edge.targetHandle || !el.targetHandle && !edge.targetHandle));
}
function rendererPointToPoint(_ref9, _ref0) {
  let x = _ref9.x,
    y = _ref9.y;
  let tx = _ref0.x,
    ty = _ref0.y,
    tScale = _ref0.zoom;
  return {
    x: x * tScale + tx,
    y: y * tScale + ty
  };
}
function pointToRendererPoint(_ref1, _ref10, snapToGrid, snapGrid) {
  let x = _ref1.x,
    y = _ref1.y;
  let tx = _ref10.x,
    ty = _ref10.y,
    tScale = _ref10.zoom;
  if (snapToGrid === void 0) {
    snapToGrid = false;
  }
  if (snapGrid === void 0) {
    snapGrid = [1, 1];
  }
  const position = {
    x: (x - tx) / tScale,
    y: (y - ty) / tScale
  };
  return snapToGrid ? snapPosition(position, snapGrid) : position;
}
function getBoundsOfBoxes(box1, box2) {
  return {
    x: Math.min(box1.x, box2.x),
    y: Math.min(box1.y, box2.y),
    x2: Math.max(box1.x2, box2.x2),
    y2: Math.max(box1.y2, box2.y2)
  };
}
function rectToBox(_ref11) {
  let x = _ref11.x,
    y = _ref11.y,
    width = _ref11.width,
    height = _ref11.height;
  return {
    x,
    y,
    x2: x + width,
    y2: y + height
  };
}
function boxToRect(_ref12) {
  let x = _ref12.x,
    y = _ref12.y,
    x2 = _ref12.x2,
    y2 = _ref12.y2;
  return {
    x,
    y,
    width: x2 - x,
    height: y2 - y
  };
}
function getBoundsofRects(rect1, rect2) {
  return boxToRect(getBoundsOfBoxes(rectToBox(rect1), rectToBox(rect2)));
}
function getRectOfNodes(nodes) {
  let box = {
    x: Number.POSITIVE_INFINITY,
    y: Number.POSITIVE_INFINITY,
    x2: Number.NEGATIVE_INFINITY,
    y2: Number.NEGATIVE_INFINITY
  };
  for (let i = 0; i < nodes.length; i++) {
    const node = nodes[i];
    box = getBoundsOfBoxes(box, rectToBox(_extends({}, node.computedPosition, node.dimensions)));
  }
  return boxToRect(box);
}
function getNodesInside(nodes, rect, viewport, partially, excludeNonSelectableNodes) {
  if (viewport === void 0) {
    viewport = {
      x: 0,
      y: 0,
      zoom: 1
    };
  }
  if (partially === void 0) {
    partially = false;
  }
  if (excludeNonSelectableNodes === void 0) {
    excludeNonSelectableNodes = false;
  }
  const paneRect = _extends({}, pointToRendererPoint(rect, viewport), {
    width: rect.width / viewport.zoom,
    height: rect.height / viewport.zoom
  });
  const visibleNodes = [];
  for (const node of nodes) {
    var _ref13, _dimensions$width, _ref14, _dimensions$height;
    const dimensions = node.dimensions,
      _node$selectable = node.selectable,
      selectable = _node$selectable === void 0 ? true : _node$selectable,
      _node$hidden = node.hidden,
      hidden = _node$hidden === void 0 ? false : _node$hidden;
    const width = (_ref13 = (_dimensions$width = dimensions.width) != null ? _dimensions$width : node.width) != null ? _ref13 : null;
    const height = (_ref14 = (_dimensions$height = dimensions.height) != null ? _dimensions$height : node.height) != null ? _ref14 : null;
    if (excludeNonSelectableNodes && !selectable || hidden) {
      continue;
    }
    const overlappingArea = getOverlappingArea(paneRect, nodeToRect(node));
    const notInitialized = width === null || height === null;
    const partiallyVisible = partially && overlappingArea > 0;
    const area = (width != null ? width : 0) * (height != null ? height : 0);
    const isVisible = notInitialized || partiallyVisible || overlappingArea >= area;
    if (isVisible || node.dragging) {
      visibleNodes.push(node);
    }
  }
  return visibleNodes;
}
function getConnectedEdges(nodesOrId, edges) {
  const nodeIds = /* @__PURE__ */new Set();
  if (typeof nodesOrId === "string") {
    nodeIds.add(nodesOrId);
  } else if (nodesOrId.length >= 1) {
    for (const n of nodesOrId) {
      nodeIds.add(n.id);
    }
  }
  return edges.filter(edge => nodeIds.has(edge.source) || nodeIds.has(edge.target));
}
function parsePadding(padding, viewport) {
  if (typeof padding === "number") {
    return Math.floor((viewport - viewport / (1 + padding)) * 0.5);
  }
  if (typeof padding === "string" && padding.endsWith("px")) {
    const paddingValue = Number.parseFloat(padding);
    if (!Number.isNaN(paddingValue)) {
      return Math.floor(paddingValue);
    }
  }
  if (typeof padding === "string" && padding.endsWith("%")) {
    const paddingValue = Number.parseFloat(padding);
    if (!Number.isNaN(paddingValue)) {
      return Math.floor(viewport * paddingValue * 0.01);
    }
  }
  warn("The padding value \"" + padding + "\" is invalid. Please provide a number or a string with a valid unit (px or %).");
  return 0;
}
function parsePaddings(padding, width, height) {
  if (typeof padding === "string" || typeof padding === "number") {
    const paddingY = parsePadding(padding, height);
    const paddingX = parsePadding(padding, width);
    return {
      top: paddingY,
      right: paddingX,
      bottom: paddingY,
      left: paddingX,
      x: paddingX * 2,
      y: paddingY * 2
    };
  }
  if (typeof padding === "object") {
    var _ref15, _padding$top, _ref16, _padding$bottom, _ref17, _padding$left, _ref18, _padding$right;
    const top = parsePadding((_ref15 = (_padding$top = padding.top) != null ? _padding$top : padding.y) != null ? _ref15 : 0, height);
    const bottom = parsePadding((_ref16 = (_padding$bottom = padding.bottom) != null ? _padding$bottom : padding.y) != null ? _ref16 : 0, height);
    const left = parsePadding((_ref17 = (_padding$left = padding.left) != null ? _padding$left : padding.x) != null ? _ref17 : 0, width);
    const right = parsePadding((_ref18 = (_padding$right = padding.right) != null ? _padding$right : padding.x) != null ? _ref18 : 0, width);
    return {
      top,
      right,
      bottom,
      left,
      x: left + right,
      y: top + bottom
    };
  }
  return {
    top: 0,
    right: 0,
    bottom: 0,
    left: 0,
    x: 0,
    y: 0
  };
}
function calculateAppliedPaddings(bounds, x, y, zoom2, width, height) {
  const _rendererPointToPoint = rendererPointToPoint(bounds, {
      x,
      y,
      zoom: zoom2
    }),
    left = _rendererPointToPoint.x,
    top = _rendererPointToPoint.y;
  const _rendererPointToPoint2 = rendererPointToPoint({
      x: bounds.x + bounds.width,
      y: bounds.y + bounds.height
    }, {
      x,
      y,
      zoom: zoom2
    }),
    boundRight = _rendererPointToPoint2.x,
    boundBottom = _rendererPointToPoint2.y;
  const right = width - boundRight;
  const bottom = height - boundBottom;
  return {
    left: Math.floor(left),
    top: Math.floor(top),
    right: Math.floor(right),
    bottom: Math.floor(bottom)
  };
}
function getTransformForBounds(bounds, width, height, minZoom, maxZoom, padding) {
  if (padding === void 0) {
    padding = 0.1;
  }
  const p = parsePaddings(padding, width, height);
  const xZoom = (width - p.x) / bounds.width;
  const yZoom = (height - p.y) / bounds.height;
  const zoom2 = Math.min(xZoom, yZoom);
  const clampedZoom = clamp(zoom2, minZoom, maxZoom);
  const boundsCenterX = bounds.x + bounds.width / 2;
  const boundsCenterY = bounds.y + bounds.height / 2;
  const x = width / 2 - boundsCenterX * clampedZoom;
  const y = height / 2 - boundsCenterY * clampedZoom;
  const newPadding = calculateAppliedPaddings(bounds, x, y, clampedZoom, width, height);
  const offset = {
    left: Math.min(newPadding.left - p.left, 0),
    top: Math.min(newPadding.top - p.top, 0),
    right: Math.min(newPadding.right - p.right, 0),
    bottom: Math.min(newPadding.bottom - p.bottom, 0)
  };
  return {
    x: x - offset.left + offset.right,
    y: y - offset.top + offset.bottom,
    zoom: clampedZoom
  };
}
function getXYZPos(parentPos, computedPosition) {
  return {
    x: computedPosition.x + parentPos.x,
    y: computedPosition.y + parentPos.y,
    z: (parentPos.z > computedPosition.z ? parentPos.z : computedPosition.z) + 1
  };
}
function isParentSelected(node, nodeLookup) {
  if (!node.parentNode) {
    return false;
  }
  const parent = nodeLookup.get(node.parentNode);
  if (!parent) {
    return false;
  }
  if (parent.selected) {
    return true;
  }
  return isParentSelected(parent, nodeLookup);
}
function getMarkerId(marker, vueFlowId) {
  if (typeof marker === "undefined") {
    return "";
  }
  if (typeof marker === "string") {
    return marker;
  }
  const idPrefix = vueFlowId ? vueFlowId + "__" : "";
  return "" + idPrefix + Object.keys(marker).sort().map(key => key + "=" + marker[key]).join("&");
}
function wheelDelta(event) {
  const factor = event.ctrlKey && isMacOs() ? 10 : 1;
  return -event.deltaY * (event.deltaMode === 1 ? 0.05 : event.deltaMode ? 1 : 2e-3) * factor;
}
function calcAutoPanVelocity(value, min, max) {
  if (value < min) {
    return clamp(Math.abs(value - min), 1, min) / min;
  }
  if (value > max) {
    return -clamp(Math.abs(value - max), 1, min) / min;
  }
  return 0;
}
function calcAutoPan(pos, bounds, speed, distance2) {
  if (speed === void 0) {
    speed = 15;
  }
  if (distance2 === void 0) {
    distance2 = 40;
  }
  const xMovement = calcAutoPanVelocity(pos.x, distance2, bounds.width - distance2) * speed;
  const yMovement = calcAutoPanVelocity(pos.y, distance2, bounds.height - distance2) * speed;
  return [xMovement, yMovement];
}
function handleParentExpand(updateItem, parent) {
  if (parent) {
    const extendWidth = updateItem.position.x + updateItem.dimensions.width - parent.dimensions.width;
    const extendHeight = updateItem.position.y + updateItem.dimensions.height - parent.dimensions.height;
    if (extendWidth > 0 || extendHeight > 0 || updateItem.position.x < 0 || updateItem.position.y < 0) {
      var _parentStyles$width, _parentStyles$height;
      let parentStyles = {};
      if (typeof parent.style === "function") {
        parentStyles = _extends({}, parent.style(parent));
      } else if (parent.style) {
        parentStyles = _extends({}, parent.style);
      }
      parentStyles.width = (_parentStyles$width = parentStyles.width) != null ? _parentStyles$width : parent.dimensions.width + "px";
      parentStyles.height = (_parentStyles$height = parentStyles.height) != null ? _parentStyles$height : parent.dimensions.height + "px";
      if (extendWidth > 0) {
        if (typeof parentStyles.width === "string") {
          const currWidth = Number(parentStyles.width.replace("px", ""));
          parentStyles.width = currWidth + extendWidth + "px";
        } else {
          parentStyles.width += extendWidth;
        }
      }
      if (extendHeight > 0) {
        if (typeof parentStyles.height === "string") {
          const currWidth = Number(parentStyles.height.replace("px", ""));
          parentStyles.height = currWidth + extendHeight + "px";
        } else {
          parentStyles.height += extendHeight;
        }
      }
      if (updateItem.position.x < 0) {
        const xDiff = Math.abs(updateItem.position.x);
        parent.position.x = parent.position.x - xDiff;
        if (typeof parentStyles.width === "string") {
          const currWidth = Number(parentStyles.width.replace("px", ""));
          parentStyles.width = currWidth + xDiff + "px";
        } else {
          parentStyles.width += xDiff;
        }
        updateItem.position.x = 0;
      }
      if (updateItem.position.y < 0) {
        const yDiff = Math.abs(updateItem.position.y);
        parent.position.y = parent.position.y - yDiff;
        if (typeof parentStyles.height === "string") {
          const currWidth = Number(parentStyles.height.replace("px", ""));
          parentStyles.height = currWidth + yDiff + "px";
        } else {
          parentStyles.height += yDiff;
        }
        updateItem.position.y = 0;
      }
      parent.dimensions.width = Number(parentStyles.width.toString().replace("px", ""));
      parent.dimensions.height = Number(parentStyles.height.toString().replace("px", ""));
      if (typeof parent.style === "function") {
        parent.style = p => {
          const styleFunc = parent.style;
          return _extends({}, styleFunc(p), parentStyles);
        };
      } else {
        parent.style = _extends({}, parent.style, parentStyles);
      }
    }
  }
}
function applyChanges(changes, elements) {
  var _a, _b;
  const addRemoveChanges = changes.filter(c => c.type === "add" || c.type === "remove");
  for (const change of addRemoveChanges) {
    if (change.type === "add") {
      const index = elements.findIndex(el => el.id === change.item.id);
      if (index === -1) {
        elements.push(change.item);
      }
    } else if (change.type === "remove") {
      const index = elements.findIndex(el => el.id === change.id);
      if (index !== -1) {
        elements.splice(index, 1);
      }
    }
  }
  const elementIds = elements.map(el => el.id);
  for (const element of elements) {
    for (const currentChange of changes) {
      if (currentChange.id !== element.id) {
        continue;
      }
      switch (currentChange.type) {
        case "select":
          element.selected = currentChange.selected;
          break;
        case "position":
          if (isGraphNode(element)) {
            if (typeof currentChange.position !== "undefined") {
              element.position = currentChange.position;
            }
            if (typeof currentChange.dragging !== "undefined") {
              element.dragging = currentChange.dragging;
            }
            if (element.expandParent && element.parentNode) {
              const parent = elements[elementIds.indexOf(element.parentNode)];
              if (parent && isGraphNode(parent)) {
                handleParentExpand(element, parent);
              }
            }
          }
          break;
        case "dimensions":
          if (isGraphNode(element)) {
            if (typeof currentChange.dimensions !== "undefined") {
              element.dimensions = currentChange.dimensions;
            }
            if (typeof currentChange.updateStyle !== "undefined" && currentChange.updateStyle) {
              element.style = _extends({}, element.style || {}, {
                width: ((_a = currentChange.dimensions) == null ? void 0 : _a.width) + "px",
                height: ((_b = currentChange.dimensions) == null ? void 0 : _b.height) + "px"
              });
            }
            if (typeof currentChange.resizing !== "undefined") {
              element.resizing = currentChange.resizing;
            }
            if (element.expandParent && element.parentNode) {
              const parent = elements[elementIds.indexOf(element.parentNode)];
              if (parent && isGraphNode(parent)) {
                const parentInit = !!parent.dimensions.width && !!parent.dimensions.height;
                if (!parentInit) {
                  nextTick(() => {
                    handleParentExpand(element, parent);
                  });
                } else {
                  handleParentExpand(element, parent);
                }
              }
            }
          }
          break;
      }
    }
  }
  return elements;
}
function createSelectionChange(id2, selected) {
  return {
    id: id2,
    type: "select",
    selected
  };
}
function createAdditionChange(item) {
  return {
    item,
    type: "add"
  };
}
function createNodeRemoveChange(id2) {
  return {
    id: id2,
    type: "remove"
  };
}
function createEdgeRemoveChange(id2, source, target, sourceHandle, targetHandle) {
  return {
    id: id2,
    source,
    target,
    sourceHandle: sourceHandle || null,
    targetHandle: targetHandle || null,
    type: "remove"
  };
}
function getSelectionChanges(items, selectedIds, mutateItem) {
  if (selectedIds === void 0) {
    selectedIds = /* @__PURE__ */new Set();
  }
  if (mutateItem === void 0) {
    mutateItem = false;
  }
  const changes = [];
  for (const _ref19 of items) {
    const id2 = _ref19[0];
    const item = _ref19[1];
    const willBeSelected = selectedIds.has(id2);
    if (!(item.selected === void 0 && !willBeSelected) && item.selected !== willBeSelected) {
      if (mutateItem) {
        item.selected = willBeSelected;
      }
      changes.push(createSelectionChange(item.id, willBeSelected));
    }
  }
  return changes;
}
const noop$1 = () => {};
function createExtendedEventHook(defaultHandler) {
  const listeners = /* @__PURE__ */new Set();
  let emitter = noop$1;
  let hasEmitListeners = () => false;
  const hasListeners = () => listeners.size > 0 || hasEmitListeners();
  const setEmitter = fn => {
    emitter = fn;
  };
  const removeEmitter = () => {
    emitter = noop$1;
  };
  const setHasEmitListeners = fn => {
    hasEmitListeners = fn;
  };
  const removeHasEmitListeners = () => {
    hasEmitListeners = () => false;
  };
  const off = fn => {
    listeners.delete(fn);
  };
  const on = fn => {
    listeners.add(fn);
    const offFn = () => off(fn);
    tryOnScopeDispose(offFn);
    return {
      off: offFn
    };
  };
  const trigger = param => {
    const queue = [emitter];
    if (hasListeners()) {
      queue.push(...listeners);
    } else if (defaultHandler) {
      queue.push(defaultHandler);
    }
    return Promise.allSettled(queue.map(fn => fn(param)));
  };
  return {
    on,
    off,
    trigger,
    hasListeners,
    listeners,
    setEmitter,
    removeEmitter,
    setHasEmitListeners,
    removeHasEmitListeners
  };
}
function hasSelector(target, selector2, node) {
  let current = target;
  do {
    if (current && current.matches(selector2)) {
      return true;
    } else if (current === node) {
      return false;
    }
    current = current.parentElement;
  } while (current);
  return false;
}
function getDragItems(nodeLookup, nodesDraggable, mousePos, nodeId) {
  var _a, _b;
  const dragItems = /* @__PURE__ */new Map();
  for (const _ref20 of nodeLookup) {
    const id2 = _ref20[0];
    const node = _ref20[1];
    if ((node.selected || node.id === nodeId) && (!node.parentNode || !isParentSelected(node, nodeLookup)) && (node.draggable || nodesDraggable && typeof node.draggable === "undefined")) {
      const internalNode = nodeLookup.get(id2);
      if (internalNode) {
        dragItems.set(id2, {
          id: node.id,
          position: node.position || {
            x: 0,
            y: 0
          },
          distance: {
            x: mousePos.x - ((_a = node.computedPosition) == null ? void 0 : _a.x) || 0,
            y: mousePos.y - ((_b = node.computedPosition) == null ? void 0 : _b.y) || 0
          },
          from: {
            x: node.computedPosition.x,
            y: node.computedPosition.y
          },
          extent: node.extent,
          parentNode: node.parentNode,
          dimensions: _extends({}, node.dimensions),
          expandParent: node.expandParent
        });
      }
    }
  }
  return Array.from(dragItems.values());
}
function getEventHandlerParams(_ref21) {
  let id2 = _ref21.id,
    dragItems = _ref21.dragItems,
    findNode = _ref21.findNode;
  const extendedDragItems = [];
  for (const dragItem of dragItems) {
    const node = findNode(dragItem.id);
    if (node) {
      extendedDragItems.push(node);
    }
  }
  return [id2 ? extendedDragItems.find(n => n.id === id2) : extendedDragItems[0], extendedDragItems];
}
function getExtentPadding(padding) {
  if (Array.isArray(padding)) {
    switch (padding.length) {
      case 1:
        return [padding[0], padding[0], padding[0], padding[0]];
      case 2:
        return [padding[0], padding[1], padding[0], padding[1]];
      case 3:
        return [padding[0], padding[1], padding[2], padding[1]];
      case 4:
        return padding;
      default:
        return [0, 0, 0, 0];
    }
  }
  return [padding, padding, padding, padding];
}
function getParentExtent(currentExtent, node, parent) {
  const _ref22 = typeof currentExtent !== "string" ? getExtentPadding(currentExtent.padding) : [0, 0, 0, 0],
    top = _ref22[0],
    right = _ref22[1],
    bottom = _ref22[2],
    left = _ref22[3];
  if (parent && typeof parent.computedPosition.x !== "undefined" && typeof parent.computedPosition.y !== "undefined" && typeof parent.dimensions.width !== "undefined" && typeof parent.dimensions.height !== "undefined") {
    return [[parent.computedPosition.x + left, parent.computedPosition.y + top], [parent.computedPosition.x + parent.dimensions.width - right, parent.computedPosition.y + parent.dimensions.height - bottom]];
  }
  return false;
}
function getExtent(item, triggerError, extent, parent) {
  let currentExtent = item.extent || extent;
  if ((currentExtent === "parent" || !Array.isArray(currentExtent) && (currentExtent == null ? void 0 : currentExtent.range) === "parent") && !item.expandParent) {
    if (item.parentNode && parent && item.dimensions.width && item.dimensions.height) {
      const parentExtent = getParentExtent(currentExtent, item, parent);
      if (parentExtent) {
        currentExtent = parentExtent;
      }
    } else {
      triggerError(new VueFlowError(ErrorCode.NODE_EXTENT_INVALID, item.id));
      currentExtent = extent;
    }
  } else if (Array.isArray(currentExtent)) {
    const parentX = (parent == null ? void 0 : parent.computedPosition.x) || 0;
    const parentY = (parent == null ? void 0 : parent.computedPosition.y) || 0;
    currentExtent = [[currentExtent[0][0] + parentX, currentExtent[0][1] + parentY], [currentExtent[1][0] + parentX, currentExtent[1][1] + parentY]];
  } else if (currentExtent !== "parent" && (currentExtent == null ? void 0 : currentExtent.range) && Array.isArray(currentExtent.range)) {
    const _getExtentPadding = getExtentPadding(currentExtent.padding),
      top = _getExtentPadding[0],
      right = _getExtentPadding[1],
      bottom = _getExtentPadding[2],
      left = _getExtentPadding[3];
    const parentX = (parent == null ? void 0 : parent.computedPosition.x) || 0;
    const parentY = (parent == null ? void 0 : parent.computedPosition.y) || 0;
    currentExtent = [[currentExtent.range[0][0] + parentX + left, currentExtent.range[0][1] + parentY + top], [currentExtent.range[1][0] + parentX - right, currentExtent.range[1][1] + parentY - bottom]];
  }
  return currentExtent === "parent" ? [[Number.NEGATIVE_INFINITY, Number.NEGATIVE_INFINITY], [Number.POSITIVE_INFINITY, Number.POSITIVE_INFINITY]] : currentExtent;
}
function clampNodeExtent(_ref23, extent) {
  let width = _ref23.width,
    height = _ref23.height;
  return [extent[0], [extent[1][0] - (width || 0), extent[1][1] - (height || 0)]];
}
function calcNextPosition(node, nextPosition, triggerError, nodeExtent, parentNode) {
  const extent = clampNodeExtent(node.dimensions, getExtent(node, triggerError, nodeExtent, parentNode));
  const clampedPos = clampPosition(nextPosition, extent);
  return {
    position: {
      x: clampedPos.x - ((parentNode == null ? void 0 : parentNode.computedPosition.x) || 0),
      y: clampedPos.y - ((parentNode == null ? void 0 : parentNode.computedPosition.y) || 0)
    },
    computedPosition: clampedPos
  };
}
function getHandlePosition(node, handle, fallbackPosition, center) {
  var _ref24, _ref25, _ref27;
  if (fallbackPosition === void 0) {
    fallbackPosition = Position.Left;
  }
  if (center === void 0) {
    center = false;
  }
  const x = ((_ref24 = handle == null ? void 0 : handle.x) != null ? _ref24 : 0) + node.computedPosition.x;
  const y = ((_ref25 = handle == null ? void 0 : handle.y) != null ? _ref25 : 0) + node.computedPosition.y;
  const _ref26 = handle != null ? handle : getNodeDimensions(node),
    width = _ref26.width,
    height = _ref26.height;
  if (center) {
    return {
      x: x + width / 2,
      y: y + height / 2
    };
  }
  const position = (_ref27 = handle == null ? void 0 : handle.position) != null ? _ref27 : fallbackPosition;
  switch (position) {
    case Position.Top:
      return {
        x: x + width / 2,
        y
      };
    case Position.Right:
      return {
        x: x + width,
        y: y + height / 2
      };
    case Position.Bottom:
      return {
        x: x + width / 2,
        y: y + height
      };
    case Position.Left:
      return {
        x,
        y: y + height / 2
      };
  }
}
function getEdgeHandle(bounds, handleId) {
  if (!bounds) {
    return null;
  }
  return (!handleId ? bounds[0] : bounds.find(d => d.id === handleId)) || null;
}
function isEdgeVisible(_ref28) {
  let sourcePos = _ref28.sourcePos,
    targetPos = _ref28.targetPos,
    sourceWidth = _ref28.sourceWidth,
    sourceHeight = _ref28.sourceHeight,
    targetWidth = _ref28.targetWidth,
    targetHeight = _ref28.targetHeight,
    width = _ref28.width,
    height = _ref28.height,
    viewport = _ref28.viewport;
  const edgeBox = {
    x: Math.min(sourcePos.x, targetPos.x),
    y: Math.min(sourcePos.y, targetPos.y),
    x2: Math.max(sourcePos.x + sourceWidth, targetPos.x + targetWidth),
    y2: Math.max(sourcePos.y + sourceHeight, targetPos.y + targetHeight)
  };
  if (edgeBox.x === edgeBox.x2) {
    edgeBox.x2 += 1;
  }
  if (edgeBox.y === edgeBox.y2) {
    edgeBox.y2 += 1;
  }
  const viewBox = rectToBox({
    x: (0 - viewport.x) / viewport.zoom,
    y: (0 - viewport.y) / viewport.zoom,
    width: width / viewport.zoom,
    height: height / viewport.zoom
  });
  const xOverlap = Math.max(0, Math.min(viewBox.x2, edgeBox.x2) - Math.max(viewBox.x, edgeBox.x));
  const yOverlap = Math.max(0, Math.min(viewBox.y2, edgeBox.y2) - Math.max(viewBox.y, edgeBox.y));
  const overlappingArea = Math.ceil(xOverlap * yOverlap);
  return overlappingArea > 0;
}
function getEdgeZIndex(edge, findNode, elevateEdgesOnSelect) {
  if (elevateEdgesOnSelect === void 0) {
    elevateEdgesOnSelect = false;
  }
  const hasZIndex = typeof edge.zIndex === "number";
  let z = hasZIndex ? edge.zIndex : 0;
  const source = findNode(edge.source);
  const target = findNode(edge.target);
  if (!source || !target) {
    return 0;
  }
  if (elevateEdgesOnSelect) {
    z = hasZIndex ? edge.zIndex : Math.max(source.computedPosition.z || 0, target.computedPosition.z || 0);
  }
  return z;
}
var ErrorCode = /* @__PURE__ */(ErrorCode2 => {
  ErrorCode2["MISSING_STYLES"] = "MISSING_STYLES";
  ErrorCode2["MISSING_VIEWPORT_DIMENSIONS"] = "MISSING_VIEWPORT_DIMENSIONS";
  ErrorCode2["NODE_INVALID"] = "NODE_INVALID";
  ErrorCode2["NODE_NOT_FOUND"] = "NODE_NOT_FOUND";
  ErrorCode2["NODE_MISSING_PARENT"] = "NODE_MISSING_PARENT";
  ErrorCode2["NODE_TYPE_MISSING"] = "NODE_TYPE_MISSING";
  ErrorCode2["NODE_EXTENT_INVALID"] = "NODE_EXTENT_INVALID";
  ErrorCode2["EDGE_INVALID"] = "EDGE_INVALID";
  ErrorCode2["EDGE_NOT_FOUND"] = "EDGE_NOT_FOUND";
  ErrorCode2["EDGE_SOURCE_MISSING"] = "EDGE_SOURCE_MISSING";
  ErrorCode2["EDGE_TARGET_MISSING"] = "EDGE_TARGET_MISSING";
  ErrorCode2["EDGE_TYPE_MISSING"] = "EDGE_TYPE_MISSING";
  ErrorCode2["EDGE_SOURCE_TARGET_SAME"] = "EDGE_SOURCE_TARGET_SAME";
  ErrorCode2["EDGE_SOURCE_TARGET_MISSING"] = "EDGE_SOURCE_TARGET_MISSING";
  ErrorCode2["EDGE_ORPHANED"] = "EDGE_ORPHANED";
  ErrorCode2["USEVUEFLOW_OPTIONS"] = "USEVUEFLOW_OPTIONS";
  return ErrorCode2;
})(ErrorCode || {});
const messages = {
  ["MISSING_STYLES"
  /* MISSING_STYLES */]: () => "It seems that you haven't loaded the necessary styles. Please import '@vue-flow/core/dist/style.css' to ensure that the graph is rendered correctly",
  ["MISSING_VIEWPORT_DIMENSIONS"
  /* MISSING_VIEWPORT_DIMENSIONS */]: () => "The Vue Flow parent container needs a width and a height to render the graph",
  ["NODE_INVALID"
  /* NODE_INVALID */]: id2 => "Node is invalid\nNode: " + id2,
  ["NODE_NOT_FOUND"
  /* NODE_NOT_FOUND */]: id2 => "Node not found\nNode: " + id2,
  ["NODE_MISSING_PARENT"
  /* NODE_MISSING_PARENT */]: (id2, parentId) => "Node is missing a parent\nNode: " + id2 + "\nParent: " + parentId,
  ["NODE_TYPE_MISSING"
  /* NODE_TYPE_MISSING */]: type => "Node type is missing\nType: " + type,
  ["NODE_EXTENT_INVALID"
  /* NODE_EXTENT_INVALID */]: id2 => "Only child nodes can use a parent extent\nNode: " + id2,
  ["EDGE_INVALID"
  /* EDGE_INVALID */]: id2 => "An edge needs a source and a target\nEdge: " + id2,
  ["EDGE_SOURCE_MISSING"
  /* EDGE_SOURCE_MISSING */]: (id2, source) => "Edge source is missing\nEdge: " + id2 + " \nSource: " + source,
  ["EDGE_TARGET_MISSING"
  /* EDGE_TARGET_MISSING */]: (id2, target) => "Edge target is missing\nEdge: " + id2 + " \nTarget: " + target,
  ["EDGE_TYPE_MISSING"
  /* EDGE_TYPE_MISSING */]: type => "Edge type is missing\nType: " + type,
  ["EDGE_SOURCE_TARGET_SAME"
  /* EDGE_SOURCE_TARGET_SAME */]: (id2, source, target) => "Edge source and target are the same\nEdge: " + id2 + " \nSource: " + source + " \nTarget: " + target,
  ["EDGE_SOURCE_TARGET_MISSING"
  /* EDGE_SOURCE_TARGET_MISSING */]: (id2, source, target) => "Edge source or target is missing\nEdge: " + id2 + " \nSource: " + source + " \nTarget: " + target,
  ["EDGE_ORPHANED"
  /* EDGE_ORPHANED */]: id2 => "Edge was orphaned (suddenly missing source or target) and has been removed\nEdge: " + id2,
  ["EDGE_NOT_FOUND"
  /* EDGE_NOT_FOUND */]: id2 => "Edge not found\nEdge: " + id2,
  // deprecation errors
  ["USEVUEFLOW_OPTIONS"
  /* USEVUEFLOW_OPTIONS */]: () => "The options parameter is deprecated and will be removed in the next major version. Please use the id parameter instead"
};
class VueFlowError extends Error {
  constructor(code) {
    var _a;
    for (var _len1 = arguments.length, args = new Array(_len1 > 1 ? _len1 - 1 : 0), _key1 = 1; _key1 < _len1; _key1++) {
      args[_key1 - 1] = arguments[_key1];
    }
    super((_a = messages[code]) == null ? void 0 : _a.call(messages, ...args));
    this.name = "VueFlowError";
    this.code = code;
    this.args = args;
  }
}
function isMouseEvent(event) {
  return "clientX" in event;
}
function isUseDragEvent(event) {
  return "sourceEvent" in event;
}
function getEventPosition(event, bounds) {
  var _ref29, _ref30;
  const isMouse = isMouseEvent(event);
  let evtX;
  let evtY;
  if (isMouse) {
    evtX = event.clientX;
    evtY = event.clientY;
  } else if ("touches" in event && event.touches.length > 0) {
    evtX = event.touches[0].clientX;
    evtY = event.touches[0].clientY;
  } else if ("changedTouches" in event && event.changedTouches.length > 0) {
    evtX = event.changedTouches[0].clientX;
    evtY = event.changedTouches[0].clientY;
  } else {
    evtX = 0;
    evtY = 0;
  }
  return {
    x: evtX - ((_ref29 = bounds == null ? void 0 : bounds.left) != null ? _ref29 : 0),
    y: evtY - ((_ref30 = bounds == null ? void 0 : bounds.top) != null ? _ref30 : 0)
  };
}
const isMacOs = () => {
  var _a;
  return typeof navigator !== "undefined" && ((_a = navigator == null ? void 0 : navigator.userAgent) == null ? void 0 : _a.indexOf("Mac")) >= 0;
};
function getNodeDimensions(node) {
  var _ref31, _ref32, _ref33, _ref34;
  var _a, _b;
  return {
    width: (_ref31 = (_ref32 = (_a = node.dimensions) == null ? void 0 : _a.width) != null ? _ref32 : node.width) != null ? _ref31 : 0,
    height: (_ref33 = (_ref34 = (_b = node.dimensions) == null ? void 0 : _b.height) != null ? _ref34 : node.height) != null ? _ref33 : 0
  };
}
function snapPosition(position, snapGrid) {
  if (snapGrid === void 0) {
    snapGrid = [1, 1];
  }
  return {
    x: snapGrid[0] * Math.round(position.x / snapGrid[0]),
    y: snapGrid[1] * Math.round(position.y / snapGrid[1])
  };
}
const alwaysValid$1 = () => true;
function resetRecentHandle(handleDomNode) {
  handleDomNode == null ? void 0 : handleDomNode.classList.remove("valid", "connecting", "vue-flow__handle-valid", "vue-flow__handle-connecting");
}
function getNodesWithinDistance(position, nodeLookup, distance2) {
  const nodes = [];
  const rect = {
    x: position.x - distance2,
    y: position.y - distance2,
    width: distance2 * 2,
    height: distance2 * 2
  };
  for (const node of nodeLookup.values()) {
    if (getOverlappingArea(rect, nodeToRect(node)) > 0) {
      nodes.push(node);
    }
  }
  return nodes;
}
const ADDITIONAL_DISTANCE = 250;
function getClosestHandle(position, connectionRadius, nodeLookup, fromHandle) {
  var _a, _b;
  let closestHandles = [];
  let minDistance = Number.POSITIVE_INFINITY;
  const closeNodes = getNodesWithinDistance(position, nodeLookup, connectionRadius + ADDITIONAL_DISTANCE);
  for (const node of closeNodes) {
    var _ref35, _ref36;
    const allHandles = [...((_ref35 = (_a = node.handleBounds) == null ? void 0 : _a.source) != null ? _ref35 : []), ...((_ref36 = (_b = node.handleBounds) == null ? void 0 : _b.target) != null ? _ref36 : [])];
    for (const handle of allHandles) {
      if (fromHandle.nodeId === handle.nodeId && fromHandle.type === handle.type && fromHandle.id === handle.id) {
        continue;
      }
      const _getHandlePosition = getHandlePosition(node, handle, handle.position, true),
        x = _getHandlePosition.x,
        y = _getHandlePosition.y;
      const distance2 = Math.sqrt((x - position.x) ** 2 + (y - position.y) ** 2);
      if (distance2 > connectionRadius) {
        continue;
      }
      if (distance2 < minDistance) {
        closestHandles = [_extends({}, handle, {
          x,
          y
        })];
        minDistance = distance2;
      } else if (distance2 === minDistance) {
        closestHandles.push(_extends({}, handle, {
          x,
          y
        }));
      }
    }
  }
  if (!closestHandles.length) {
    return null;
  }
  if (closestHandles.length > 1) {
    var _closestHandles$find;
    const oppositeHandleType = fromHandle.type === "source" ? "target" : "source";
    return (_closestHandles$find = closestHandles.find(handle => handle.type === oppositeHandleType)) != null ? _closestHandles$find : closestHandles[0];
  }
  return closestHandles[0];
}
function isValidHandle(event, _ref37, edges, nodes, findNode, nodeLookup) {
  let handle = _ref37.handle,
    connectionMode = _ref37.connectionMode,
    fromNodeId = _ref37.fromNodeId,
    fromHandleId = _ref37.fromHandleId,
    fromType = _ref37.fromType,
    doc = _ref37.doc,
    lib = _ref37.lib,
    flowId = _ref37.flowId,
    _ref37$isValidConnect = _ref37.isValidConnection,
    isValidConnection = _ref37$isValidConnect === void 0 ? alwaysValid$1 : _ref37$isValidConnect;
  const isTarget = fromType === "target";
  const handleDomNode = handle ? doc.querySelector("." + lib + "-flow__handle[data-id=\"" + flowId + "-" + (handle == null ? void 0 : handle.nodeId) + "-" + (handle == null ? void 0 : handle.id) + "-" + (handle == null ? void 0 : handle.type) + "\"]") : null;
  const _getEventPosition = getEventPosition(event),
    x = _getEventPosition.x,
    y = _getEventPosition.y;
  const handleBelow = doc.elementFromPoint(x, y);
  const handleToCheck = (handleBelow == null ? void 0 : handleBelow.classList.contains(lib + "-flow__handle")) ? handleBelow : handleDomNode;
  const result = {
    handleDomNode: handleToCheck,
    isValid: false,
    connection: null,
    toHandle: null
  };
  if (handleToCheck) {
    const handleType = getHandleType(void 0, handleToCheck);
    const handleNodeId = handleToCheck.getAttribute("data-nodeid");
    const handleId = handleToCheck.getAttribute("data-handleid");
    const connectable = handleToCheck.classList.contains("connectable");
    const connectableEnd = handleToCheck.classList.contains("connectableend");
    if (!handleNodeId || !handleType) {
      return result;
    }
    const connection = {
      source: isTarget ? handleNodeId : fromNodeId,
      sourceHandle: isTarget ? handleId : fromHandleId,
      target: isTarget ? fromNodeId : handleNodeId,
      targetHandle: isTarget ? fromHandleId : handleId
    };
    result.connection = connection;
    const isConnectable = connectable && connectableEnd;
    const isValid = isConnectable && (connectionMode === ConnectionMode.Strict ? isTarget && handleType === "source" || !isTarget && handleType === "target" : handleNodeId !== fromNodeId || handleId !== fromHandleId);
    result.isValid = isValid && isValidConnection(connection, {
      nodes,
      edges,
      sourceNode: findNode(connection.source),
      targetNode: findNode(connection.target)
    });
    result.toHandle = getHandle(handleNodeId, handleType, handleId, nodeLookup, connectionMode, true);
  }
  return result;
}
function getHandleType(edgeUpdaterType, handleDomNode) {
  if (edgeUpdaterType) {
    return edgeUpdaterType;
  } else if (handleDomNode == null ? void 0 : handleDomNode.classList.contains("target")) {
    return "target";
  } else if (handleDomNode == null ? void 0 : handleDomNode.classList.contains("source")) {
    return "source";
  }
  return null;
}
function getConnectionStatus(isInsideConnectionRadius, isHandleValid) {
  let connectionStatus = null;
  if (isHandleValid) {
    connectionStatus = "valid";
  } else if (isInsideConnectionRadius && !isHandleValid) {
    connectionStatus = "invalid";
  }
  return connectionStatus;
}
function isConnectionValid(isInsideConnectionRadius, isHandleValid) {
  let isValid = null;
  if (isHandleValid) {
    isValid = true;
  } else if (isInsideConnectionRadius && !isHandleValid) {
    isValid = false;
  }
  return isValid;
}
function getHandle(nodeId, handleType, handleId, nodeLookup, connectionMode, withAbsolutePosition) {
  var _ref38, _ref39, _ref40;
  if (withAbsolutePosition === void 0) {
    withAbsolutePosition = false;
  }
  var _a, _b, _c;
  const node = nodeLookup.get(nodeId);
  if (!node) {
    return null;
  }
  const handles = connectionMode === ConnectionMode.Strict ? (_a = node.handleBounds) == null ? void 0 : _a[handleType] : [...((_ref38 = (_b = node.handleBounds) == null ? void 0 : _b.source) != null ? _ref38 : []), ...((_ref39 = (_c = node.handleBounds) == null ? void 0 : _c.target) != null ? _ref39 : [])];
  const handle = (_ref40 = handleId ? handles == null ? void 0 : handles.find(h2 => h2.id === handleId) : handles == null ? void 0 : handles[0]) != null ? _ref40 : null;
  return handle && withAbsolutePosition ? _extends({}, handle, getHandlePosition(node, handle, handle.position, true)) : handle;
}
const oppositePosition = {
  [Position.Left]: Position.Right,
  [Position.Right]: Position.Left,
  [Position.Top]: Position.Bottom,
  [Position.Bottom]: Position.Top
};
const productionEnvs = ["production", "prod"];
function warn(message) {
  if (isDev()) {
    for (var _len10 = arguments.length, args = new Array(_len10 > 1 ? _len10 - 1 : 0), _key10 = 1; _key10 < _len10; _key10++) {
      args[_key10 - 1] = arguments[_key10];
    }
    console.warn("[Vue Flow]: " + message, ...args);
  }
}
function isDev() {
  return !productionEnvs.includes("production");
}
function getHandleBounds(type, nodeElement, nodeBounds, zoom2, nodeId) {
  const handles = nodeElement.querySelectorAll(".vue-flow__handle." + type);
  if (!(handles == null ? void 0 : handles.length)) {
    return null;
  }
  return Array.from(handles).map(handle => {
    const handleBounds = handle.getBoundingClientRect();
    return _extends({
      id: handle.getAttribute("data-handleid"),
      type,
      nodeId,
      position: handle.getAttribute("data-handlepos"),
      x: (handleBounds.left - nodeBounds.left) / zoom2,
      y: (handleBounds.top - nodeBounds.top) / zoom2
    }, getDimensions(handle));
  });
}
function handleNodeClick(node, multiSelectionActive, addSelectedNodes, removeSelectedNodes, nodesSelectionActive, unselect, nodeEl) {
  if (unselect === void 0) {
    unselect = false;
  }
  nodesSelectionActive.value = false;
  if (!node.selected) {
    addSelectedNodes([node]);
  } else if (unselect || node.selected && multiSelectionActive) {
    removeSelectedNodes([node]);
    nextTick(() => {
      nodeEl.blur();
    });
  }
}
function isDef(val) {
  const unrefVal = unref(val);
  return typeof unrefVal !== "undefined";
}
function addEdgeToStore(edgeParams, edges, triggerError, defaultEdgeOptions) {
  if (!edgeParams || !edgeParams.source || !edgeParams.target) {
    var _ref41;
    triggerError(new VueFlowError(ErrorCode.EDGE_INVALID, (_ref41 = edgeParams == null ? void 0 : edgeParams.id) != null ? _ref41 : "[ID UNKNOWN]"));
    return false;
  }
  let edge;
  if (isEdge(edgeParams)) {
    edge = edgeParams;
  } else {
    edge = _extends({}, edgeParams, {
      id: getEdgeId(edgeParams)
    });
  }
  edge = parseEdge(edge, void 0, defaultEdgeOptions);
  if (connectionExists(edge, edges)) {
    return false;
  }
  return edge;
}
function updateEdgeAction(edge, newConnection, prevEdge, shouldReplaceId, triggerError) {
  if (!newConnection.source || !newConnection.target) {
    triggerError(new VueFlowError(ErrorCode.EDGE_INVALID, edge.id));
    return false;
  }
  if (!prevEdge) {
    triggerError(new VueFlowError(ErrorCode.EDGE_NOT_FOUND, edge.id));
    return false;
  }
  const id2 = edge.id,
    rest = _objectWithoutPropertiesLoose(edge, _excluded3);
  return _extends({}, rest, {
    id: shouldReplaceId ? getEdgeId(newConnection) : id2,
    source: newConnection.source,
    target: newConnection.target,
    sourceHandle: newConnection.sourceHandle,
    targetHandle: newConnection.targetHandle
  });
}
function createGraphNodes(nodes, findNode, triggerError) {
  const parentNodes = {};
  const nextNodes = [];
  for (let i = 0; i < nodes.length; ++i) {
    const node = nodes[i];
    if (!isNode(node)) {
      triggerError(new VueFlowError(ErrorCode.NODE_INVALID, node == null ? void 0 : node.id) || "[ID UNKNOWN|INDEX " + i + "]");
      continue;
    }
    const parsed = parseNode(node, findNode(node.id), node.parentNode);
    if (node.parentNode) {
      parentNodes[node.parentNode] = true;
    }
    nextNodes[i] = parsed;
  }
  for (const node of nextNodes) {
    const parentNode = findNode(node.parentNode) || nextNodes.find(n => n.id === node.parentNode);
    if (node.parentNode && !parentNode) {
      triggerError(new VueFlowError(ErrorCode.NODE_MISSING_PARENT, node.id, node.parentNode));
    }
    if (node.parentNode || parentNodes[node.id]) {
      if (parentNodes[node.id]) {
        node.isParent = true;
      }
      if (parentNode) {
        parentNode.isParent = true;
      }
    }
  }
  return nextNodes;
}
function addConnectionToLookup(type, connection, connectionKey, connectionLookup, nodeId, handleId) {
  let key = nodeId;
  const nodeMap = connectionLookup.get(key) || /* @__PURE__ */new Map();
  connectionLookup.set(key, nodeMap.set(connectionKey, connection));
  key = nodeId + "-" + type;
  const typeMap = connectionLookup.get(key) || /* @__PURE__ */new Map();
  connectionLookup.set(key, typeMap.set(connectionKey, connection));
  if (handleId) {
    key = nodeId + "-" + type + "-" + handleId;
    const handleMap = connectionLookup.get(key) || /* @__PURE__ */new Map();
    connectionLookup.set(key, handleMap.set(connectionKey, connection));
  }
}
function updateConnectionLookup(connectionLookup, edgeLookup, edges) {
  connectionLookup.clear();
  for (const edge of edges) {
    const sourceNode = edge.source,
      targetNode = edge.target,
      _edge$sourceHandle = edge.sourceHandle,
      sourceHandle = _edge$sourceHandle === void 0 ? null : _edge$sourceHandle,
      _edge$targetHandle = edge.targetHandle,
      targetHandle = _edge$targetHandle === void 0 ? null : _edge$targetHandle;
    const connection = {
      edgeId: edge.id,
      source: sourceNode,
      target: targetNode,
      sourceHandle,
      targetHandle
    };
    const sourceKey = sourceNode + "-" + sourceHandle + "--" + targetNode + "-" + targetHandle;
    const targetKey = targetNode + "-" + targetHandle + "--" + sourceNode + "-" + sourceHandle;
    addConnectionToLookup("source", connection, targetKey, connectionLookup, sourceNode, sourceHandle);
    addConnectionToLookup("target", connection, sourceKey, connectionLookup, targetNode, targetHandle);
  }
}
function areSetsEqual(a, b) {
  if (a.size !== b.size) {
    return false;
  }
  for (const item of a) {
    if (!b.has(item)) {
      return false;
    }
  }
  return true;
}
function createGraphEdges(nextEdges, isValidConnection, findNode, findEdge, onError, defaultEdgeOptions, nodes, edges) {
  const validEdges = [];
  for (const edgeOrConnection of nextEdges) {
    const edge = isEdge(edgeOrConnection) ? edgeOrConnection : addEdgeToStore(edgeOrConnection, edges, onError, defaultEdgeOptions);
    if (!edge) {
      continue;
    }
    const sourceNode = findNode(edge.source);
    const targetNode = findNode(edge.target);
    if (!sourceNode || !targetNode) {
      onError(new VueFlowError(ErrorCode.EDGE_SOURCE_TARGET_MISSING, edge.id, edge.source, edge.target));
      continue;
    }
    if (!sourceNode) {
      onError(new VueFlowError(ErrorCode.EDGE_SOURCE_MISSING, edge.id, edge.source));
      continue;
    }
    if (!targetNode) {
      onError(new VueFlowError(ErrorCode.EDGE_TARGET_MISSING, edge.id, edge.target));
      continue;
    }
    if (isValidConnection) {
      const isValid = isValidConnection(edge, {
        edges,
        nodes,
        sourceNode,
        targetNode
      });
      if (!isValid) {
        onError(new VueFlowError(ErrorCode.EDGE_INVALID, edge.id));
        continue;
      }
    }
    const existingEdge = findEdge(edge.id);
    validEdges.push(_extends({}, parseEdge(edge, existingEdge, defaultEdgeOptions), {
      sourceNode,
      targetNode
    }));
  }
  return validEdges;
}
const VueFlow = Symbol("vueFlow");
const NodeId = Symbol("nodeId");
const NodeRef = Symbol("nodeRef");
const EdgeId = Symbol("edgeId");
const EdgeRef = Symbol("edgeRef");
const Slots$1 = Symbol("slots");
function useDrag(params) {
  const _useVueFlow = useVueFlow(),
    vueFlowRef = _useVueFlow.vueFlowRef,
    snapToGrid = _useVueFlow.snapToGrid,
    snapGrid = _useVueFlow.snapGrid,
    noDragClassName = _useVueFlow.noDragClassName,
    nodeLookup = _useVueFlow.nodeLookup,
    nodeExtent = _useVueFlow.nodeExtent,
    nodeDragThreshold = _useVueFlow.nodeDragThreshold,
    viewport = _useVueFlow.viewport,
    autoPanOnNodeDrag = _useVueFlow.autoPanOnNodeDrag,
    autoPanSpeed = _useVueFlow.autoPanSpeed,
    nodesDraggable = _useVueFlow.nodesDraggable,
    panBy = _useVueFlow.panBy,
    findNode = _useVueFlow.findNode,
    multiSelectionActive = _useVueFlow.multiSelectionActive,
    nodesSelectionActive = _useVueFlow.nodesSelectionActive,
    selectNodesOnDrag = _useVueFlow.selectNodesOnDrag,
    removeSelectedElements = _useVueFlow.removeSelectedElements,
    addSelectedNodes = _useVueFlow.addSelectedNodes,
    updateNodePositions = _useVueFlow.updateNodePositions,
    emits = _useVueFlow.emits;
  const onStart = params.onStart,
    onDrag = params.onDrag,
    onStop = params.onStop,
    onClick = params.onClick,
    el = params.el,
    disabled = params.disabled,
    id2 = params.id,
    selectable = params.selectable,
    dragHandle = params.dragHandle;
  const dragging = shallowRef(false);
  let dragItems = [];
  let dragHandler;
  let containerBounds = null;
  let lastPos = {
    x: void 0,
    y: void 0
  };
  let mousePosition = {
    x: 0,
    y: 0
  };
  let dragEvent = null;
  let dragStarted = false;
  let nodePositionsChanged = false;
  let autoPanId = 0;
  let autoPanStarted = false;
  const getPointerPosition = useGetPointerPosition();
  const updateNodes = _ref42 => {
    let x = _ref42.x,
      y = _ref42.y;
    lastPos = {
      x,
      y
    };
    let hasChange = false;
    dragItems = dragItems.map(n => {
      const nextPosition = {
        x: x - n.distance.x,
        y: y - n.distance.y
      };
      const _calcNextPosition = calcNextPosition(n, snapToGrid.value ? snapPosition(nextPosition, snapGrid.value) : nextPosition, emits.error, nodeExtent.value, n.parentNode ? findNode(n.parentNode) : void 0),
        computedPosition = _calcNextPosition.computedPosition;
      hasChange = hasChange || n.position.x !== computedPosition.x || n.position.y !== computedPosition.y;
      n.position = computedPosition;
      return n;
    });
    nodePositionsChanged = nodePositionsChanged || hasChange;
    if (!hasChange) {
      return;
    }
    updateNodePositions(dragItems, true, true);
    dragging.value = true;
    if (dragEvent) {
      const _getEventHandlerParam = getEventHandlerParams({
          id: id2,
          dragItems,
          findNode
        }),
        currentNode = _getEventHandlerParam[0],
        nodes = _getEventHandlerParam[1];
      onDrag({
        event: dragEvent,
        node: currentNode,
        nodes
      });
    }
  };
  const autoPan = () => {
    if (!containerBounds) {
      return;
    }
    const _calcAutoPan = calcAutoPan(mousePosition, containerBounds, autoPanSpeed.value),
      xMovement = _calcAutoPan[0],
      yMovement = _calcAutoPan[1];
    if (xMovement !== 0 || yMovement !== 0) {
      var _lastPos$x, _lastPos$y;
      const nextPos = {
        x: ((_lastPos$x = lastPos.x) != null ? _lastPos$x : 0) - xMovement / viewport.value.zoom,
        y: ((_lastPos$y = lastPos.y) != null ? _lastPos$y : 0) - yMovement / viewport.value.zoom
      };
      if (panBy({
        x: xMovement,
        y: yMovement
      })) {
        updateNodes(nextPos);
      }
    }
    autoPanId = requestAnimationFrame(autoPan);
  };
  const startDrag = (event, nodeEl) => {
    dragStarted = true;
    const node = findNode(id2);
    if (!selectNodesOnDrag.value && !multiSelectionActive.value && node) {
      if (!node.selected) {
        removeSelectedElements();
      }
    }
    if (node && toValue$1(selectable) && selectNodesOnDrag.value) {
      handleNodeClick(node, multiSelectionActive.value, addSelectedNodes, removeSelectedElements, nodesSelectionActive, false, nodeEl);
    }
    const pointerPos = getPointerPosition(event.sourceEvent);
    lastPos = pointerPos;
    dragItems = getDragItems(nodeLookup.value, nodesDraggable.value, pointerPos, id2);
    if (dragItems.length) {
      const _getEventHandlerParam2 = getEventHandlerParams({
          id: id2,
          dragItems,
          findNode
        }),
        currentNode = _getEventHandlerParam2[0],
        nodes = _getEventHandlerParam2[1];
      onStart({
        event: event.sourceEvent,
        node: currentNode,
        nodes
      });
    }
  };
  const eventStart = (event, nodeEl) => {
    var _a;
    if (event.sourceEvent.type === "touchmove" && event.sourceEvent.touches.length > 1) {
      return;
    }
    nodePositionsChanged = false;
    if (nodeDragThreshold.value === 0) {
      startDrag(event, nodeEl);
    }
    lastPos = getPointerPosition(event.sourceEvent);
    containerBounds = ((_a = vueFlowRef.value) == null ? void 0 : _a.getBoundingClientRect()) || null;
    mousePosition = getEventPosition(event.sourceEvent, containerBounds);
  };
  const eventDrag = (event, nodeEl) => {
    const pointerPos = getPointerPosition(event.sourceEvent);
    if (!autoPanStarted && dragStarted && autoPanOnNodeDrag.value) {
      autoPanStarted = true;
      autoPan();
    }
    if (!dragStarted) {
      var _lastPos$x2, _lastPos$y2;
      const x = pointerPos.xSnapped - ((_lastPos$x2 = lastPos.x) != null ? _lastPos$x2 : 0);
      const y = pointerPos.ySnapped - ((_lastPos$y2 = lastPos.y) != null ? _lastPos$y2 : 0);
      const distance2 = Math.sqrt(x * x + y * y);
      if (distance2 > nodeDragThreshold.value) {
        startDrag(event, nodeEl);
      }
    }
    if ((lastPos.x !== pointerPos.xSnapped || lastPos.y !== pointerPos.ySnapped) && dragItems.length && dragStarted) {
      dragEvent = event.sourceEvent;
      mousePosition = getEventPosition(event.sourceEvent, containerBounds);
      updateNodes(pointerPos);
    }
  };
  const eventEnd = event => {
    let isClick = false;
    if (!dragStarted && !dragging.value && !multiSelectionActive.value) {
      var _lastPos$x3, _lastPos$y3;
      const evt = event.sourceEvent;
      const pointerPos = getPointerPosition(evt);
      const x = pointerPos.xSnapped - ((_lastPos$x3 = lastPos.x) != null ? _lastPos$x3 : 0);
      const y = pointerPos.ySnapped - ((_lastPos$y3 = lastPos.y) != null ? _lastPos$y3 : 0);
      const distance2 = Math.sqrt(x * x + y * y);
      if (distance2 !== 0 && distance2 <= nodeDragThreshold.value) {
        onClick == null ? void 0 : onClick(evt);
        isClick = true;
      }
    }
    if (dragItems.length && !isClick) {
      if (nodePositionsChanged) {
        updateNodePositions(dragItems, false, false);
        nodePositionsChanged = false;
      }
      const _getEventHandlerParam3 = getEventHandlerParams({
          id: id2,
          dragItems,
          findNode
        }),
        currentNode = _getEventHandlerParam3[0],
        nodes = _getEventHandlerParam3[1];
      onStop({
        event: event.sourceEvent,
        node: currentNode,
        nodes
      });
    }
    dragItems = [];
    dragging.value = false;
    autoPanStarted = false;
    dragStarted = false;
    lastPos = {
      x: void 0,
      y: void 0
    };
    cancelAnimationFrame(autoPanId);
  };
  watch([() => toValue$1(disabled), el], (_ref43, _, onCleanup) => {
    let isDisabled = _ref43[0],
      nodeEl = _ref43[1];
    if (nodeEl) {
      const selection2 = select$1(nodeEl);
      if (!isDisabled) {
        dragHandler = drag().on("start", event => eventStart(event, nodeEl)).on("drag", event => eventDrag(event, nodeEl)).on("end", event => eventEnd(event)).filter(event => {
          const target = event.target;
          const unrefDragHandle = toValue$1(dragHandle);
          return !event.button && (!noDragClassName.value || !hasSelector(target, "." + noDragClassName.value, nodeEl) && (!unrefDragHandle || hasSelector(target, unrefDragHandle, nodeEl)));
        });
        selection2.call(dragHandler);
      }
      onCleanup(() => {
        selection2.on(".drag", null);
        if (dragHandler) {
          dragHandler.on("start", null);
          dragHandler.on("drag", null);
          dragHandler.on("end", null);
        }
      });
    }
  });
  return dragging;
}
function createEdgeHooks() {
  return {
    doubleClick: createExtendedEventHook(),
    click: createExtendedEventHook(),
    mouseEnter: createExtendedEventHook(),
    mouseMove: createExtendedEventHook(),
    mouseLeave: createExtendedEventHook(),
    contextMenu: createExtendedEventHook(),
    updateStart: createExtendedEventHook(),
    update: createExtendedEventHook(),
    updateEnd: createExtendedEventHook()
  };
}
function useEdgeHooks(edge, emits) {
  const edgeHooks = createEdgeHooks();
  edgeHooks.doubleClick.on(event => {
    var _a, _b;
    emits.edgeDoubleClick(event);
    (_b = (_a = edge.events) == null ? void 0 : _a.doubleClick) == null ? void 0 : _b.call(_a, event);
  });
  edgeHooks.click.on(event => {
    var _a, _b;
    emits.edgeClick(event);
    (_b = (_a = edge.events) == null ? void 0 : _a.click) == null ? void 0 : _b.call(_a, event);
  });
  edgeHooks.mouseEnter.on(event => {
    var _a, _b;
    emits.edgeMouseEnter(event);
    (_b = (_a = edge.events) == null ? void 0 : _a.mouseEnter) == null ? void 0 : _b.call(_a, event);
  });
  edgeHooks.mouseMove.on(event => {
    var _a, _b;
    emits.edgeMouseMove(event);
    (_b = (_a = edge.events) == null ? void 0 : _a.mouseMove) == null ? void 0 : _b.call(_a, event);
  });
  edgeHooks.mouseLeave.on(event => {
    var _a, _b;
    emits.edgeMouseLeave(event);
    (_b = (_a = edge.events) == null ? void 0 : _a.mouseLeave) == null ? void 0 : _b.call(_a, event);
  });
  edgeHooks.contextMenu.on(event => {
    var _a, _b;
    emits.edgeContextMenu(event);
    (_b = (_a = edge.events) == null ? void 0 : _a.contextMenu) == null ? void 0 : _b.call(_a, event);
  });
  edgeHooks.updateStart.on(event => {
    var _a, _b;
    emits.edgeUpdateStart(event);
    (_b = (_a = edge.events) == null ? void 0 : _a.updateStart) == null ? void 0 : _b.call(_a, event);
  });
  edgeHooks.update.on(event => {
    var _a, _b;
    emits.edgeUpdate(event);
    (_b = (_a = edge.events) == null ? void 0 : _a.update) == null ? void 0 : _b.call(_a, event);
  });
  edgeHooks.updateEnd.on(event => {
    var _a, _b;
    emits.edgeUpdateEnd(event);
    (_b = (_a = edge.events) == null ? void 0 : _a.updateEnd) == null ? void 0 : _b.call(_a, event);
  });
  return Object.entries(edgeHooks).reduce((hooks, _ref44) => {
    let key = _ref44[0],
      value = _ref44[1];
    hooks.emit[key] = value.trigger;
    hooks.on[key] = value.on;
    return hooks;
  }, {
    emit: {},
    on: {}
  });
}
function useGetPointerPosition() {
  const _useVueFlow3 = useVueFlow(),
    viewport = _useVueFlow3.viewport,
    snapGrid = _useVueFlow3.snapGrid,
    snapToGrid = _useVueFlow3.snapToGrid,
    vueFlowRef = _useVueFlow3.vueFlowRef;
  return event => {
    var _ref45;
    var _a;
    const containerBounds = (_ref45 = (_a = vueFlowRef.value) == null ? void 0 : _a.getBoundingClientRect()) != null ? _ref45 : {
      left: 0,
      top: 0
    };
    const evt = isUseDragEvent(event) ? event.sourceEvent : event;
    const _getEventPosition2 = getEventPosition(evt, containerBounds),
      x = _getEventPosition2.x,
      y = _getEventPosition2.y;
    const pointerPos = pointToRendererPoint({
      x,
      y
    }, viewport.value);
    const _ref46 = snapToGrid.value ? snapPosition(pointerPos, snapGrid.value) : pointerPos,
      xSnapped = _ref46.x,
      ySnapped = _ref46.y;
    return _extends({
      xSnapped,
      ySnapped
    }, pointerPos);
  };
}
function alwaysValid() {
  return true;
}
function useHandle(_ref47) {
  let handleId = _ref47.handleId,
    nodeId = _ref47.nodeId,
    type = _ref47.type,
    isValidConnection = _ref47.isValidConnection,
    edgeUpdaterType = _ref47.edgeUpdaterType,
    onEdgeUpdate = _ref47.onEdgeUpdate,
    onEdgeUpdateEnd = _ref47.onEdgeUpdateEnd;
  const _useVueFlow4 = useVueFlow(),
    flowId = _useVueFlow4.id,
    vueFlowRef = _useVueFlow4.vueFlowRef,
    connectionMode = _useVueFlow4.connectionMode,
    connectionRadius = _useVueFlow4.connectionRadius,
    connectOnClick = _useVueFlow4.connectOnClick,
    connectionClickStartHandle = _useVueFlow4.connectionClickStartHandle,
    nodesConnectable = _useVueFlow4.nodesConnectable,
    autoPanOnConnect = _useVueFlow4.autoPanOnConnect,
    autoPanSpeed = _useVueFlow4.autoPanSpeed,
    findNode = _useVueFlow4.findNode,
    panBy = _useVueFlow4.panBy,
    startConnection = _useVueFlow4.startConnection,
    updateConnection = _useVueFlow4.updateConnection,
    endConnection = _useVueFlow4.endConnection,
    emits = _useVueFlow4.emits,
    viewport = _useVueFlow4.viewport,
    edges = _useVueFlow4.edges,
    nodes = _useVueFlow4.nodes,
    isValidConnectionProp = _useVueFlow4.isValidConnection,
    nodeLookup = _useVueFlow4.nodeLookup;
  let connection = null;
  let isValid = false;
  let handleDomNode = null;
  function handlePointerDown(event) {
    var _a;
    const isTarget = toValue$1(type) === "target";
    const isMouseTriggered = isMouseEvent(event);
    const doc = getHostForElement(event.target);
    const clickedHandle = event.currentTarget;
    if (clickedHandle && (isMouseTriggered && event.button === 0 || !isMouseTriggered)) {
      let onPointerMove = function onPointerMove(event2) {
          connectionPosition = getEventPosition(event2, containerBounds);
          closestHandle = getClosestHandle(pointToRendererPoint(connectionPosition, viewport.value, false, [1, 1]), connectionRadius.value, nodeLookup.value, fromHandle);
          if (!autoPanStarted) {
            autoPan();
            autoPanStarted = true;
          }
          const result = isValidHandle(event2, {
            handle: closestHandle,
            connectionMode: connectionMode.value,
            fromNodeId: toValue$1(nodeId),
            fromHandleId: toValue$1(handleId),
            fromType: isTarget ? "target" : "source",
            isValidConnection: isValidConnectionHandler,
            doc,
            lib: "vue",
            flowId,
            nodeLookup: nodeLookup.value
          }, edges.value, nodes.value, findNode, nodeLookup.value);
          handleDomNode = result.handleDomNode;
          connection = result.connection;
          isValid = isConnectionValid(!!closestHandle, result.isValid);
          const newConnection2 = _extends({}, previousConnection, {
            isValid,
            to: result.toHandle && isValid ? rendererPointToPoint({
              x: result.toHandle.x,
              y: result.toHandle.y
            }, viewport.value) : connectionPosition,
            toHandle: result.toHandle,
            toPosition: isValid && result.toHandle ? result.toHandle.position : oppositePosition[fromHandle.position],
            toNode: result.toHandle ? nodeLookup.value.get(result.toHandle.nodeId) : null
          });
          if (isValid && closestHandle && (previousConnection == null ? void 0 : previousConnection.toHandle) && newConnection2.toHandle && previousConnection.toHandle.type === newConnection2.toHandle.type && previousConnection.toHandle.nodeId === newConnection2.toHandle.nodeId && previousConnection.toHandle.id === newConnection2.toHandle.id && previousConnection.to.x === newConnection2.to.x && previousConnection.to.y === newConnection2.to.y) {
            return;
          }
          const connectingHandle = closestHandle != null ? closestHandle : result.toHandle;
          updateConnection(connectingHandle && isValid ? rendererPointToPoint({
            x: connectingHandle.x,
            y: connectingHandle.y
          }, viewport.value) : connectionPosition, connectingHandle, getConnectionStatus(!!connectingHandle, isValid));
          previousConnection = newConnection2;
          if (!closestHandle && !isValid && !handleDomNode) {
            return resetRecentHandle(prevActiveHandle);
          }
          if (connection && connection.source !== connection.target && handleDomNode) {
            resetRecentHandle(prevActiveHandle);
            prevActiveHandle = handleDomNode;
            handleDomNode.classList.add("connecting", "vue-flow__handle-connecting");
            handleDomNode.classList.toggle("valid", !!isValid);
            handleDomNode.classList.toggle("vue-flow__handle-valid", !!isValid);
          }
        },
        _onPointerUp = function onPointerUp(event2) {
          if ("touches" in event2 && event2.touches.length > 0) {
            return;
          }
          if ((closestHandle || handleDomNode) && connection && isValid) {
            if (!onEdgeUpdate) {
              emits.connect(connection);
            } else {
              onEdgeUpdate(event2, connection);
            }
          }
          emits.connectEnd(event2);
          if (edgeUpdaterType) {
            onEdgeUpdateEnd == null ? void 0 : onEdgeUpdateEnd(event2);
          }
          resetRecentHandle(prevActiveHandle);
          cancelAnimationFrame(autoPanId);
          endConnection(event2);
          autoPanStarted = false;
          isValid = false;
          connection = null;
          handleDomNode = null;
          doc.removeEventListener("mousemove", onPointerMove);
          doc.removeEventListener("mouseup", _onPointerUp);
          doc.removeEventListener("touchmove", onPointerMove);
          doc.removeEventListener("touchend", _onPointerUp);
        };
      const node = findNode(toValue$1(nodeId));
      let isValidConnectionHandler = toValue$1(isValidConnection) || isValidConnectionProp.value || alwaysValid;
      if (!isValidConnectionHandler && node) {
        isValidConnectionHandler = (!isTarget ? node.isValidTargetPos : node.isValidSourcePos) || alwaysValid;
      }
      let closestHandle;
      let autoPanId = 0;
      const _getEventPosition3 = getEventPosition(event),
        x = _getEventPosition3.x,
        y = _getEventPosition3.y;
      const handleType = getHandleType(toValue$1(edgeUpdaterType), clickedHandle);
      const containerBounds = (_a = vueFlowRef.value) == null ? void 0 : _a.getBoundingClientRect();
      if (!containerBounds || !handleType) {
        return;
      }
      const fromHandleInternal = getHandle(toValue$1(nodeId), handleType, toValue$1(handleId), nodeLookup.value, connectionMode.value);
      if (!fromHandleInternal) {
        return;
      }
      let prevActiveHandle;
      let connectionPosition = getEventPosition(event, containerBounds);
      let autoPanStarted = false;
      const autoPan = () => {
        if (!autoPanOnConnect.value) {
          return;
        }
        const _calcAutoPan2 = calcAutoPan(connectionPosition, containerBounds, autoPanSpeed.value),
          xMovement = _calcAutoPan2[0],
          yMovement = _calcAutoPan2[1];
        panBy({
          x: xMovement,
          y: yMovement
        });
        autoPanId = requestAnimationFrame(autoPan);
      };
      const fromHandle = _extends({}, fromHandleInternal, {
        nodeId: toValue$1(nodeId),
        type: handleType,
        position: fromHandleInternal.position
      });
      const fromNodeInternal = nodeLookup.value.get(toValue$1(nodeId));
      const from = getHandlePosition(fromNodeInternal, fromHandle, Position.Left, true);
      const newConnection = {
        inProgress: true,
        isValid: null,
        from,
        fromHandle,
        fromPosition: fromHandle.position,
        fromNode: fromNodeInternal,
        to: connectionPosition,
        toHandle: null,
        toPosition: oppositePosition[fromHandle.position],
        toNode: null
      };
      startConnection(_extends({
        nodeId: toValue$1(nodeId),
        id: toValue$1(handleId),
        type: handleType,
        position: (clickedHandle == null ? void 0 : clickedHandle.getAttribute("data-handlepos")) || Position.Top
      }, connectionPosition), {
        x: x - containerBounds.left,
        y: y - containerBounds.top
      });
      emits.connectStart({
        event,
        nodeId: toValue$1(nodeId),
        handleId: toValue$1(handleId),
        handleType
      });
      let previousConnection = newConnection;
      doc.addEventListener("mousemove", onPointerMove);
      doc.addEventListener("mouseup", _onPointerUp);
      doc.addEventListener("touchmove", onPointerMove);
      doc.addEventListener("touchend", _onPointerUp);
    }
  }
  function handleClick(event) {
    var _connectionClickStart;
    var _a, _b;
    if (!connectOnClick.value) {
      return;
    }
    const isTarget = toValue$1(type) === "target";
    if (!connectionClickStartHandle.value) {
      emits.clickConnectStart({
        event,
        nodeId: toValue$1(nodeId),
        handleId: toValue$1(handleId)
      });
      startConnection(_extends({
        nodeId: toValue$1(nodeId),
        type: toValue$1(type),
        id: toValue$1(handleId),
        position: Position.Top
      }, getEventPosition(event)), void 0, true);
      return;
    }
    let isValidConnectionHandler = toValue$1(isValidConnection) || isValidConnectionProp.value || alwaysValid;
    const node = findNode(toValue$1(nodeId));
    if (!isValidConnectionHandler && node) {
      isValidConnectionHandler = (!isTarget ? node.isValidTargetPos : node.isValidSourcePos) || alwaysValid;
    }
    if (node && (typeof node.connectable === "undefined" ? nodesConnectable.value : node.connectable) === false) {
      return;
    }
    const doc = getHostForElement(event.target);
    const result = isValidHandle(event, {
      handle: _extends({
        nodeId: toValue$1(nodeId),
        id: toValue$1(handleId),
        type: toValue$1(type),
        position: Position.Top
      }, getEventPosition(event)),
      connectionMode: connectionMode.value,
      fromNodeId: connectionClickStartHandle.value.nodeId,
      fromHandleId: (_connectionClickStart = connectionClickStartHandle.value.id) != null ? _connectionClickStart : null,
      fromType: connectionClickStartHandle.value.type,
      isValidConnection: isValidConnectionHandler,
      doc,
      lib: "vue",
      flowId,
      nodeLookup: nodeLookup.value
    }, edges.value, nodes.value, findNode, nodeLookup.value);
    const isOwnHandle = ((_a = result.connection) == null ? void 0 : _a.source) === ((_b = result.connection) == null ? void 0 : _b.target);
    if (result.isValid && result.connection && !isOwnHandle) {
      emits.connect(result.connection);
    }
    emits.clickConnectEnd(event);
    endConnection(event, true);
  }
  return {
    handlePointerDown,
    handleClick
  };
}
function useNodeId() {
  return inject(NodeId, "");
}
function useNode(id2) {
  var _ref48;
  const nodeId = (_ref48 = id2 != null ? id2 : useNodeId()) != null ? _ref48 : "";
  const nodeEl = inject(NodeRef, ref(null));
  const _useVueFlow5 = useVueFlow(),
    findNode = _useVueFlow5.findNode,
    edges = _useVueFlow5.edges,
    emits = _useVueFlow5.emits;
  const node = findNode(nodeId);
  if (!node) {
    emits.error(new VueFlowError(ErrorCode.NODE_NOT_FOUND, nodeId));
  }
  return {
    id: nodeId,
    nodeEl,
    node,
    parentNode: computed(() => findNode(node.parentNode)),
    connectedEdges: computed(() => getConnectedEdges([node], edges.value))
  };
}
function createNodeHooks() {
  return {
    doubleClick: createExtendedEventHook(),
    click: createExtendedEventHook(),
    mouseEnter: createExtendedEventHook(),
    mouseMove: createExtendedEventHook(),
    mouseLeave: createExtendedEventHook(),
    contextMenu: createExtendedEventHook(),
    dragStart: createExtendedEventHook(),
    drag: createExtendedEventHook(),
    dragStop: createExtendedEventHook()
  };
}
function useNodeHooks(node, emits) {
  const nodeHooks = createNodeHooks();
  nodeHooks.doubleClick.on(event => {
    var _a, _b;
    emits.nodeDoubleClick(event);
    (_b = (_a = node.events) == null ? void 0 : _a.doubleClick) == null ? void 0 : _b.call(_a, event);
  });
  nodeHooks.click.on(event => {
    var _a, _b;
    emits.nodeClick(event);
    (_b = (_a = node.events) == null ? void 0 : _a.click) == null ? void 0 : _b.call(_a, event);
  });
  nodeHooks.mouseEnter.on(event => {
    var _a, _b;
    emits.nodeMouseEnter(event);
    (_b = (_a = node.events) == null ? void 0 : _a.mouseEnter) == null ? void 0 : _b.call(_a, event);
  });
  nodeHooks.mouseMove.on(event => {
    var _a, _b;
    emits.nodeMouseMove(event);
    (_b = (_a = node.events) == null ? void 0 : _a.mouseMove) == null ? void 0 : _b.call(_a, event);
  });
  nodeHooks.mouseLeave.on(event => {
    var _a, _b;
    emits.nodeMouseLeave(event);
    (_b = (_a = node.events) == null ? void 0 : _a.mouseLeave) == null ? void 0 : _b.call(_a, event);
  });
  nodeHooks.contextMenu.on(event => {
    var _a, _b;
    emits.nodeContextMenu(event);
    (_b = (_a = node.events) == null ? void 0 : _a.contextMenu) == null ? void 0 : _b.call(_a, event);
  });
  nodeHooks.dragStart.on(event => {
    var _a, _b;
    emits.nodeDragStart(event);
    (_b = (_a = node.events) == null ? void 0 : _a.dragStart) == null ? void 0 : _b.call(_a, event);
  });
  nodeHooks.drag.on(event => {
    var _a, _b;
    emits.nodeDrag(event);
    (_b = (_a = node.events) == null ? void 0 : _a.drag) == null ? void 0 : _b.call(_a, event);
  });
  nodeHooks.dragStop.on(event => {
    var _a, _b;
    emits.nodeDragStop(event);
    (_b = (_a = node.events) == null ? void 0 : _a.dragStop) == null ? void 0 : _b.call(_a, event);
  });
  return Object.entries(nodeHooks).reduce((hooks, _ref49) => {
    let key = _ref49[0],
      value = _ref49[1];
    hooks.emit[key] = value.trigger;
    hooks.on[key] = value.on;
    return hooks;
  }, {
    emit: {},
    on: {}
  });
}
function useUpdateNodePositions() {
  const _useVueFlow6 = useVueFlow(),
    getSelectedNodes = _useVueFlow6.getSelectedNodes,
    nodeExtent = _useVueFlow6.nodeExtent,
    updateNodePositions = _useVueFlow6.updateNodePositions,
    findNode = _useVueFlow6.findNode,
    snapGrid = _useVueFlow6.snapGrid,
    snapToGrid = _useVueFlow6.snapToGrid,
    nodesDraggable = _useVueFlow6.nodesDraggable,
    emits = _useVueFlow6.emits;
  return function (positionDiff, isShiftPressed) {
    if (isShiftPressed === void 0) {
      isShiftPressed = false;
    }
    const xVelo = snapToGrid.value ? snapGrid.value[0] : 5;
    const yVelo = snapToGrid.value ? snapGrid.value[1] : 5;
    const factor = isShiftPressed ? 4 : 1;
    const positionDiffX = positionDiff.x * xVelo * factor;
    const positionDiffY = positionDiff.y * yVelo * factor;
    const nodeUpdates = [];
    for (const node of getSelectedNodes.value) {
      if (node.draggable || nodesDraggable && typeof node.draggable === "undefined") {
        const nextPosition = {
          x: node.computedPosition.x + positionDiffX,
          y: node.computedPosition.y + positionDiffY
        };
        const _calcNextPosition2 = calcNextPosition(node, nextPosition, emits.error, nodeExtent.value, node.parentNode ? findNode(node.parentNode) : void 0),
          position = _calcNextPosition2.position;
        nodeUpdates.push({
          id: node.id,
          position,
          from: node.position,
          distance: {
            x: positionDiff.x,
            y: positionDiff.y
          },
          dimensions: node.dimensions
        });
      }
    }
    updateNodePositions(nodeUpdates, true, false);
  };
}
const DEFAULT_PADDING = 0.1;
const defaultEase = t => ((t *= 2) <= 1 ? t * t * t : (t -= 2) * t * t + 2) / 2;
function noop$4() {
  warn("Viewport not initialized yet.");
  return Promise.resolve(false);
}
const initialViewportHelper = {
  zoomIn: noop$4,
  zoomOut: noop$4,
  zoomTo: noop$4,
  fitView: noop$4,
  setCenter: noop$4,
  fitBounds: noop$4,
  project: position => position,
  screenToFlowCoordinate: position => position,
  flowToScreenCoordinate: position => position,
  setViewport: noop$4,
  setTransform: noop$4,
  getViewport: () => ({
    x: 0,
    y: 0,
    zoom: 1
  }),
  getTransform: () => ({
    x: 0,
    y: 0,
    zoom: 1
  }),
  viewportInitialized: false
};
function useViewportHelper(state) {
  function zoom2(scale, transitionOptions) {
    return new Promise(resolve => {
      if (state.d3Selection && state.d3Zoom) {
        state.d3Zoom.interpolate((transitionOptions == null ? void 0 : transitionOptions.interpolate) === "linear" ? interpolate$1 : interpolateZoom$1).scaleBy(getD3Transition(state.d3Selection, transitionOptions == null ? void 0 : transitionOptions.duration, transitionOptions == null ? void 0 : transitionOptions.ease, () => {
          resolve(true);
        }), scale);
      } else {
        resolve(false);
      }
    });
  }
  function transformViewport(x, y, zoom22, transitionOptions) {
    return new Promise(resolve => {
      var _a;
      const _clampPosition = clampPosition({
          x: -x,
          y: -y
        }, state.translateExtent),
        clampedX = _clampPosition.x,
        clampedY = _clampPosition.y;
      const nextTransform = identity$2.translate(-clampedX, -clampedY).scale(zoom22);
      if (state.d3Selection && state.d3Zoom) {
        (_a = state.d3Zoom) == null ? void 0 : _a.interpolate((transitionOptions == null ? void 0 : transitionOptions.interpolate) === "linear" ? interpolate$1 : interpolateZoom$1).transform(getD3Transition(state.d3Selection, transitionOptions == null ? void 0 : transitionOptions.duration, transitionOptions == null ? void 0 : transitionOptions.ease, () => {
          resolve(true);
        }), nextTransform);
      } else {
        resolve(false);
      }
    });
  }
  return computed(() => {
    const isInitialized = state.d3Zoom && state.d3Selection && state.dimensions.width && state.dimensions.height;
    if (!isInitialized) {
      return initialViewportHelper;
    }
    return {
      viewportInitialized: true,
      // todo: allow passing scale as option
      zoomIn: options => {
        return zoom2(1.2, options);
      },
      zoomOut: options => {
        return zoom2(1 / 1.2, options);
      },
      zoomTo: (zoomLevel, options) => {
        return new Promise(resolve => {
          if (state.d3Selection && state.d3Zoom) {
            state.d3Zoom.interpolate((options == null ? void 0 : options.interpolate) === "linear" ? interpolate$1 : interpolateZoom$1).scaleTo(getD3Transition(state.d3Selection, options == null ? void 0 : options.duration, options == null ? void 0 : options.ease, () => {
              resolve(true);
            }), zoomLevel);
          } else {
            resolve(false);
          }
        });
      },
      setViewport: (transform, options) => {
        return transformViewport(transform.x, transform.y, transform.zoom, options);
      },
      setTransform: (transform, options) => {
        return transformViewport(transform.x, transform.y, transform.zoom, options);
      },
      getViewport: () => ({
        x: state.viewport.x,
        y: state.viewport.y,
        zoom: state.viewport.zoom
      }),
      getTransform: () => {
        return {
          x: state.viewport.x,
          y: state.viewport.y,
          zoom: state.viewport.zoom
        };
      },
      fitView: function fitView(options) {
        var _options$minZoom, _options$maxZoom, _options$padding;
        if (options === void 0) {
          options = {
            padding: DEFAULT_PADDING,
            includeHiddenNodes: false,
            duration: 0
          };
        }
        var _a, _b;
        const nodesToFit = [];
        for (const node of state.nodes) {
          const isVisible = node.dimensions.width && node.dimensions.height && ((options == null ? void 0 : options.includeHiddenNodes) || !node.hidden);
          if (isVisible) {
            if (!((_a = options.nodes) == null ? void 0 : _a.length) || ((_b = options.nodes) == null ? void 0 : _b.length) && options.nodes.includes(node.id)) {
              nodesToFit.push(node);
            }
          }
        }
        if (!nodesToFit.length) {
          return Promise.resolve(false);
        }
        const bounds = getRectOfNodes(nodesToFit);
        const _getTransformForBound = getTransformForBounds(bounds, state.dimensions.width, state.dimensions.height, (_options$minZoom = options.minZoom) != null ? _options$minZoom : state.minZoom, (_options$maxZoom = options.maxZoom) != null ? _options$maxZoom : state.maxZoom, (_options$padding = options.padding) != null ? _options$padding : DEFAULT_PADDING),
          x = _getTransformForBound.x,
          y = _getTransformForBound.y,
          zoom22 = _getTransformForBound.zoom;
        return transformViewport(x, y, zoom22, options);
      },
      setCenter: (x, y, options) => {
        const nextZoom = typeof (options == null ? void 0 : options.zoom) !== "undefined" ? options.zoom : state.maxZoom;
        const centerX = state.dimensions.width / 2 - x * nextZoom;
        const centerY = state.dimensions.height / 2 - y * nextZoom;
        return transformViewport(centerX, centerY, nextZoom, options);
      },
      fitBounds: function fitBounds(bounds, options) {
        var _options$padding2;
        if (options === void 0) {
          options = {
            padding: DEFAULT_PADDING
          };
        }
        const _getTransformForBound2 = getTransformForBounds(bounds, state.dimensions.width, state.dimensions.height, state.minZoom, state.maxZoom, (_options$padding2 = options.padding) != null ? _options$padding2 : DEFAULT_PADDING),
          x = _getTransformForBound2.x,
          y = _getTransformForBound2.y,
          zoom22 = _getTransformForBound2.zoom;
        return transformViewport(x, y, zoom22, options);
      },
      project: position => pointToRendererPoint(position, state.viewport, state.snapToGrid, state.snapGrid),
      screenToFlowCoordinate: position => {
        if (state.vueFlowRef) {
          const _state$vueFlowRef$get = state.vueFlowRef.getBoundingClientRect(),
            domX = _state$vueFlowRef$get.x,
            domY = _state$vueFlowRef$get.y;
          const correctedPosition = {
            x: position.x - domX,
            y: position.y - domY
          };
          return pointToRendererPoint(correctedPosition, state.viewport, state.snapToGrid, state.snapGrid);
        }
        return {
          x: 0,
          y: 0
        };
      },
      flowToScreenCoordinate: position => {
        if (state.vueFlowRef) {
          const _state$vueFlowRef$get2 = state.vueFlowRef.getBoundingClientRect(),
            domX = _state$vueFlowRef$get2.x,
            domY = _state$vueFlowRef$get2.y;
          const correctedPosition = {
            x: position.x + domX,
            y: position.y + domY
          };
          return rendererPointToPoint(correctedPosition, state.viewport);
        }
        return {
          x: 0,
          y: 0
        };
      }
    };
  });
}
function getD3Transition(selection2, duration, ease, onEnd) {
  if (duration === void 0) {
    duration = 0;
  }
  if (ease === void 0) {
    ease = defaultEase;
  }
  if (onEnd === void 0) {
    onEnd = () => {};
  }
  const hasDuration = typeof duration === "number" && duration > 0;
  if (!hasDuration) {
    onEnd();
  }
  return hasDuration ? selection2.transition().duration(duration).ease(ease).on("end", onEnd) : selection2;
}
function useWatchProps(models, props, store) {
  const scope = effectScope(true);
  scope.run(() => {
    const watchModelValue = () => {
      scope.run(() => {
        let pauseModel;
        let pauseStore;
        let immediateStore = !!(store.nodes.value.length || store.edges.value.length);
        pauseModel = watchPausable([models.modelValue, () => {
          var _a, _b;
          return (_b = (_a = models.modelValue) == null ? void 0 : _a.value) == null ? void 0 : _b.length;
        }], _ref50 => {
          let elements = _ref50[0];
          if (elements && Array.isArray(elements)) {
            pauseStore == null ? void 0 : pauseStore.pause();
            store.setElements(elements);
            if (!pauseStore && !immediateStore && elements.length) {
              immediateStore = true;
            } else {
              pauseStore == null ? void 0 : pauseStore.resume();
            }
          }
        });
        pauseStore = watchPausable([store.nodes, store.edges, () => store.edges.value.length, () => store.nodes.value.length], _ref51 => {
          let nodes = _ref51[0],
            edges = _ref51[1];
          var _a;
          if (((_a = models.modelValue) == null ? void 0 : _a.value) && Array.isArray(models.modelValue.value)) {
            pauseModel == null ? void 0 : pauseModel.pause();
            models.modelValue.value = [...nodes, ...edges];
            nextTick(() => {
              pauseModel == null ? void 0 : pauseModel.resume();
            });
          }
        }, {
          immediate: immediateStore
        });
        onScopeDispose(() => {
          pauseModel == null ? void 0 : pauseModel.stop();
          pauseStore == null ? void 0 : pauseStore.stop();
        });
      });
    };
    const watchNodesValue = () => {
      scope.run(() => {
        let pauseModel;
        let pauseStore;
        let immediateStore = !!store.nodes.value.length;
        pauseModel = watchPausable([models.nodes, () => {
          var _a, _b;
          return (_b = (_a = models.nodes) == null ? void 0 : _a.value) == null ? void 0 : _b.length;
        }], _ref52 => {
          let nodes = _ref52[0];
          if (nodes && Array.isArray(nodes)) {
            pauseStore == null ? void 0 : pauseStore.pause();
            store.setNodes(nodes);
            if (!pauseStore && !immediateStore && nodes.length) {
              immediateStore = true;
            } else {
              pauseStore == null ? void 0 : pauseStore.resume();
            }
          }
        });
        pauseStore = watchPausable([store.nodes, () => store.nodes.value.length], _ref53 => {
          let nodes = _ref53[0];
          var _a;
          if (((_a = models.nodes) == null ? void 0 : _a.value) && Array.isArray(models.nodes.value)) {
            pauseModel == null ? void 0 : pauseModel.pause();
            models.nodes.value = [...nodes];
            nextTick(() => {
              pauseModel == null ? void 0 : pauseModel.resume();
            });
          }
        }, {
          immediate: immediateStore
        });
        onScopeDispose(() => {
          pauseModel == null ? void 0 : pauseModel.stop();
          pauseStore == null ? void 0 : pauseStore.stop();
        });
      });
    };
    const watchEdgesValue = () => {
      scope.run(() => {
        let pauseModel;
        let pauseStore;
        let immediateStore = !!store.edges.value.length;
        pauseModel = watchPausable([models.edges, () => {
          var _a, _b;
          return (_b = (_a = models.edges) == null ? void 0 : _a.value) == null ? void 0 : _b.length;
        }], _ref54 => {
          let edges = _ref54[0];
          if (edges && Array.isArray(edges)) {
            pauseStore == null ? void 0 : pauseStore.pause();
            store.setEdges(edges);
            if (!pauseStore && !immediateStore && edges.length) {
              immediateStore = true;
            } else {
              pauseStore == null ? void 0 : pauseStore.resume();
            }
          }
        });
        pauseStore = watchPausable([store.edges, () => store.edges.value.length], _ref55 => {
          let edges = _ref55[0];
          var _a;
          if (((_a = models.edges) == null ? void 0 : _a.value) && Array.isArray(models.edges.value)) {
            pauseModel == null ? void 0 : pauseModel.pause();
            models.edges.value = [...edges];
            nextTick(() => {
              pauseModel == null ? void 0 : pauseModel.resume();
            });
          }
        }, {
          immediate: immediateStore
        });
        onScopeDispose(() => {
          pauseModel == null ? void 0 : pauseModel.stop();
          pauseStore == null ? void 0 : pauseStore.stop();
        });
      });
    };
    const watchMaxZoom = () => {
      scope.run(() => {
        watch(() => props.maxZoom, () => {
          if (props.maxZoom && isDef(props.maxZoom)) {
            store.setMaxZoom(props.maxZoom);
          }
        }, {
          immediate: true
        });
      });
    };
    const watchMinZoom = () => {
      scope.run(() => {
        watch(() => props.minZoom, () => {
          if (props.minZoom && isDef(props.minZoom)) {
            store.setMinZoom(props.minZoom);
          }
        }, {
          immediate: true
        });
      });
    };
    const watchTranslateExtent = () => {
      scope.run(() => {
        watch(() => props.translateExtent, () => {
          if (props.translateExtent && isDef(props.translateExtent)) {
            store.setTranslateExtent(props.translateExtent);
          }
        }, {
          immediate: true
        });
      });
    };
    const watchNodeExtent = () => {
      scope.run(() => {
        watch(() => props.nodeExtent, () => {
          if (props.nodeExtent && isDef(props.nodeExtent)) {
            store.setNodeExtent(props.nodeExtent);
          }
        }, {
          immediate: true
        });
      });
    };
    const watchApplyDefault = () => {
      scope.run(() => {
        watch(() => props.applyDefault, () => {
          if (isDef(props.applyDefault)) {
            store.applyDefault.value = props.applyDefault;
          }
        }, {
          immediate: true
        });
      });
    };
    const watchAutoConnect = () => {
      scope.run(() => {
        const autoConnector = async params => {
          let connection = params;
          if (typeof props.autoConnect === "function") {
            connection = await props.autoConnect(params);
          }
          if (connection !== false) {
            store.addEdges([connection]);
          }
        };
        watch(() => props.autoConnect, () => {
          if (isDef(props.autoConnect)) {
            store.autoConnect.value = props.autoConnect;
          }
        }, {
          immediate: true
        });
        watch(store.autoConnect, (autoConnectEnabled, _, onCleanup) => {
          if (autoConnectEnabled) {
            store.onConnect(autoConnector);
          } else {
            store.hooks.value.connect.off(autoConnector);
          }
          onCleanup(() => {
            store.hooks.value.connect.off(autoConnector);
          });
        }, {
          immediate: true
        });
      });
    };
    const watchRest = () => {
      const skip = ["id", "modelValue", "translateExtent", "nodeExtent", "edges", "nodes", "maxZoom", "minZoom", "applyDefault", "autoConnect"];
      for (const key of Object.keys(props)) {
        const propKey = key;
        if (!skip.includes(propKey)) {
          const propValue = toRef(() => props[propKey]);
          const storeRef = store[propKey];
          if (isRef(storeRef)) {
            scope.run(() => {
              watch(propValue, nextValue => {
                if (isDef(nextValue)) {
                  storeRef.value = nextValue;
                }
              }, {
                immediate: true
              });
            });
          }
        }
      }
    };
    const runAll = () => {
      watchModelValue();
      watchNodesValue();
      watchEdgesValue();
      watchMinZoom();
      watchMaxZoom();
      watchTranslateExtent();
      watchNodeExtent();
      watchApplyDefault();
      watchAutoConnect();
      watchRest();
    };
    runAll();
  });
  return () => scope.stop();
}
function createHooks() {
  return {
    edgesChange: createExtendedEventHook(),
    nodesChange: createExtendedEventHook(),
    nodeDoubleClick: createExtendedEventHook(),
    nodeClick: createExtendedEventHook(),
    nodeMouseEnter: createExtendedEventHook(),
    nodeMouseMove: createExtendedEventHook(),
    nodeMouseLeave: createExtendedEventHook(),
    nodeContextMenu: createExtendedEventHook(),
    nodeDragStart: createExtendedEventHook(),
    nodeDrag: createExtendedEventHook(),
    nodeDragStop: createExtendedEventHook(),
    nodesInitialized: createExtendedEventHook(),
    miniMapNodeClick: createExtendedEventHook(),
    miniMapNodeDoubleClick: createExtendedEventHook(),
    miniMapNodeMouseEnter: createExtendedEventHook(),
    miniMapNodeMouseMove: createExtendedEventHook(),
    miniMapNodeMouseLeave: createExtendedEventHook(),
    connect: createExtendedEventHook(),
    connectStart: createExtendedEventHook(),
    connectEnd: createExtendedEventHook(),
    clickConnectStart: createExtendedEventHook(),
    clickConnectEnd: createExtendedEventHook(),
    paneReady: createExtendedEventHook(),
    init: createExtendedEventHook(),
    move: createExtendedEventHook(),
    moveStart: createExtendedEventHook(),
    moveEnd: createExtendedEventHook(),
    selectionDragStart: createExtendedEventHook(),
    selectionDrag: createExtendedEventHook(),
    selectionDragStop: createExtendedEventHook(),
    selectionContextMenu: createExtendedEventHook(),
    selectionStart: createExtendedEventHook(),
    selectionEnd: createExtendedEventHook(),
    viewportChangeStart: createExtendedEventHook(),
    viewportChange: createExtendedEventHook(),
    viewportChangeEnd: createExtendedEventHook(),
    paneScroll: createExtendedEventHook(),
    paneClick: createExtendedEventHook(),
    paneContextMenu: createExtendedEventHook(),
    paneMouseEnter: createExtendedEventHook(),
    paneMouseMove: createExtendedEventHook(),
    paneMouseLeave: createExtendedEventHook(),
    edgeContextMenu: createExtendedEventHook(),
    edgeMouseEnter: createExtendedEventHook(),
    edgeMouseMove: createExtendedEventHook(),
    edgeMouseLeave: createExtendedEventHook(),
    edgeDoubleClick: createExtendedEventHook(),
    edgeClick: createExtendedEventHook(),
    edgeUpdateStart: createExtendedEventHook(),
    edgeUpdate: createExtendedEventHook(),
    edgeUpdateEnd: createExtendedEventHook(),
    updateNodeInternals: createExtendedEventHook(),
    error: createExtendedEventHook(err => warn(err.message))
  };
}
function useHooks(emit, hooks) {
  const inst = getCurrentInstance();
  onBeforeMount(() => {
    for (const _ref56 of Object.entries(hooks.value)) {
      const key = _ref56[0];
      const value = _ref56[1];
      const listener = data => {
        emit(key, data);
      };
      value.setEmitter(listener);
      tryOnScopeDispose(value.removeEmitter);
      value.setHasEmitListeners(() => hasVNodeListener(key));
      tryOnScopeDispose(value.removeHasEmitListeners);
    }
  });
  function hasVNodeListener(event) {
    var _a;
    const key = toHandlerKey(event);
    const h2 = (_a = inst == null ? void 0 : inst.vnode.props) == null ? void 0 : _a[key];
    return !!h2;
  }
}
function toHandlerKey(event) {
  const _event$split = event.split(":"),
    head = _event$split[0],
    rest = _arrayLikeToArray(_event$split).slice(1);
  const camel = head.replace(/(?:^|-)(\w)/g, (_, c) => c.toUpperCase());
  return "on" + camel + (rest.length ? ":" + rest.join(":") : "");
}
function useState() {
  return {
    vueFlowRef: null,
    viewportRef: null,
    nodes: [],
    edges: [],
    connectionLookup: /* @__PURE__ */new Map(),
    nodeTypes: {},
    edgeTypes: {},
    initialized: false,
    dimensions: {
      width: 0,
      height: 0
    },
    viewport: {
      x: 0,
      y: 0,
      zoom: 1
    },
    d3Zoom: null,
    d3Selection: null,
    d3ZoomHandler: null,
    minZoom: 0.5,
    maxZoom: 2,
    translateExtent: [[Number.NEGATIVE_INFINITY, Number.NEGATIVE_INFINITY], [Number.POSITIVE_INFINITY, Number.POSITIVE_INFINITY]],
    nodeExtent: [[Number.NEGATIVE_INFINITY, Number.NEGATIVE_INFINITY], [Number.POSITIVE_INFINITY, Number.POSITIVE_INFINITY]],
    selectionMode: SelectionMode.Full,
    paneDragging: false,
    preventScrolling: true,
    zoomOnScroll: true,
    zoomOnPinch: true,
    zoomOnDoubleClick: true,
    panOnScroll: false,
    panOnScrollSpeed: 0.5,
    panOnScrollMode: PanOnScrollMode.Free,
    paneClickDistance: 0,
    panOnDrag: true,
    edgeUpdaterRadius: 10,
    onlyRenderVisibleElements: false,
    defaultViewport: {
      x: 0,
      y: 0,
      zoom: 1
    },
    nodesSelectionActive: false,
    userSelectionActive: false,
    userSelectionRect: null,
    defaultMarkerColor: "#b1b1b7",
    connectionLineStyle: {},
    connectionLineType: null,
    connectionLineOptions: {
      type: ConnectionLineType.Bezier,
      style: {}
    },
    connectionMode: ConnectionMode.Loose,
    connectionStartHandle: null,
    connectionEndHandle: null,
    connectionClickStartHandle: null,
    connectionPosition: {
      x: Number.NaN,
      y: Number.NaN
    },
    connectionRadius: 20,
    connectOnClick: true,
    connectionStatus: null,
    isValidConnection: null,
    snapGrid: [15, 15],
    snapToGrid: false,
    edgesUpdatable: false,
    edgesFocusable: true,
    nodesFocusable: true,
    nodesConnectable: true,
    nodesDraggable: true,
    nodeDragThreshold: 1,
    elementsSelectable: true,
    selectNodesOnDrag: true,
    multiSelectionActive: false,
    selectionKeyCode: "Shift",
    multiSelectionKeyCode: isMacOs() ? "Meta" : "Control",
    zoomActivationKeyCode: isMacOs() ? "Meta" : "Control",
    deleteKeyCode: "Backspace",
    panActivationKeyCode: "Space",
    hooks: createHooks(),
    applyDefault: true,
    autoConnect: false,
    fitViewOnInit: false,
    fitViewOnInitDone: false,
    noDragClassName: "nodrag",
    noWheelClassName: "nowheel",
    noPanClassName: "nopan",
    defaultEdgeOptions: void 0,
    elevateEdgesOnSelect: false,
    elevateNodesOnSelect: true,
    autoPanOnNodeDrag: true,
    autoPanOnConnect: true,
    autoPanSpeed: 15,
    disableKeyboardA11y: false,
    ariaLiveMessage: ""
  };
}
const storeOptionsToSkip = ["id", "vueFlowRef", "viewportRef", "initialized", "modelValue", "nodes", "edges", "maxZoom", "minZoom", "translateExtent", "hooks", "defaultEdgeOptions"];
function useActions(state, nodeLookup, edgeLookup) {
  const viewportHelper = useViewportHelper(state);
  const updateNodeInternals = ids => {
    const updateIds = ids != null ? ids : [];
    state.hooks.updateNodeInternals.trigger(updateIds);
  };
  const getIncomers$1 = nodeOrId => {
    return getIncomers(nodeOrId, state.nodes, state.edges);
  };
  const getOutgoers$1 = nodeOrId => {
    return getOutgoers(nodeOrId, state.nodes, state.edges);
  };
  const getConnectedEdges$1 = nodesOrId => {
    return getConnectedEdges(nodesOrId, state.edges);
  };
  const getHandleConnections = _ref57 => {
    var _ref58;
    let id2 = _ref57.id,
      type = _ref57.type,
      nodeId = _ref57.nodeId;
    var _a;
    const handleSuffix = id2 ? "-" + type + "-" + id2 : "-" + type;
    return Array.from((_ref58 = (_a = state.connectionLookup.get("" + nodeId + handleSuffix)) == null ? void 0 : _a.values()) != null ? _ref58 : []);
  };
  const findNode = id2 => {
    if (!id2) {
      return;
    }
    return nodeLookup.value.get(id2);
  };
  const findEdge = id2 => {
    if (!id2) {
      return;
    }
    return edgeLookup.value.get(id2);
  };
  const updateNodePositions = (dragItems, changed, dragging) => {
    var _a, _b;
    const changes = [];
    for (const node of dragItems) {
      const change = {
        id: node.id,
        type: "position",
        dragging,
        from: node.from
      };
      if (changed) {
        change.position = node.position;
        if (node.parentNode) {
          var _ref59, _ref60;
          const parentNode = findNode(node.parentNode);
          change.position = {
            x: change.position.x - ((_ref59 = (_a = parentNode == null ? void 0 : parentNode.computedPosition) == null ? void 0 : _a.x) != null ? _ref59 : 0),
            y: change.position.y - ((_ref60 = (_b = parentNode == null ? void 0 : parentNode.computedPosition) == null ? void 0 : _b.y) != null ? _ref60 : 0)
          };
        }
      }
      changes.push(change);
    }
    if (changes == null ? void 0 : changes.length) {
      state.hooks.nodesChange.trigger(changes);
    }
  };
  const updateNodeDimensions = updates => {
    if (!state.vueFlowRef) {
      return;
    }
    const viewportNode = state.vueFlowRef.querySelector(".vue-flow__transformationpane");
    if (!viewportNode) {
      return;
    }
    const style = window.getComputedStyle(viewportNode);
    const _window$DOMMatrixRead = new window.DOMMatrixReadOnly(style.transform),
      zoom2 = _window$DOMMatrixRead.m22;
    const changes = [];
    for (const element of updates) {
      const update = element;
      const node = findNode(update.id);
      if (node) {
        const dimensions = getDimensions(update.nodeElement);
        const doUpdate = !!(dimensions.width && dimensions.height && (node.dimensions.width !== dimensions.width || node.dimensions.height !== dimensions.height || update.forceUpdate));
        if (doUpdate) {
          const nodeBounds = update.nodeElement.getBoundingClientRect();
          node.dimensions = dimensions;
          node.handleBounds.source = getHandleBounds("source", update.nodeElement, nodeBounds, zoom2, node.id);
          node.handleBounds.target = getHandleBounds("target", update.nodeElement, nodeBounds, zoom2, node.id);
          changes.push({
            id: node.id,
            type: "dimensions",
            dimensions
          });
        }
      }
    }
    if (!state.fitViewOnInitDone && state.fitViewOnInit) {
      viewportHelper.value.fitView().then(() => {
        state.fitViewOnInitDone = true;
      });
    }
    if (changes.length) {
      state.hooks.nodesChange.trigger(changes);
    }
  };
  const elementSelectionHandler = (elements, selected) => {
    const nodeIds = /* @__PURE__ */new Set();
    const edgeIds = /* @__PURE__ */new Set();
    for (const element of elements) {
      if (isNode(element)) {
        nodeIds.add(element.id);
      } else if (isEdge(element)) {
        edgeIds.add(element.id);
      }
    }
    const changedNodes = getSelectionChanges(nodeLookup.value, nodeIds, true);
    const changedEdges = getSelectionChanges(edgeLookup.value, edgeIds);
    if (state.multiSelectionActive) {
      for (const nodeId of nodeIds) {
        changedNodes.push(createSelectionChange(nodeId, selected));
      }
      for (const edgeId of edgeIds) {
        changedEdges.push(createSelectionChange(edgeId, selected));
      }
    }
    if (changedNodes.length) {
      state.hooks.nodesChange.trigger(changedNodes);
    }
    if (changedEdges.length) {
      state.hooks.edgesChange.trigger(changedEdges);
    }
  };
  const addSelectedNodes = nodes => {
    if (state.multiSelectionActive) {
      const nodeChanges = nodes.map(node => createSelectionChange(node.id, true));
      state.hooks.nodesChange.trigger(nodeChanges);
      return;
    }
    state.hooks.nodesChange.trigger(getSelectionChanges(nodeLookup.value, new Set(nodes.map(n => n.id)), true));
    state.hooks.edgesChange.trigger(getSelectionChanges(edgeLookup.value));
  };
  const addSelectedEdges = edges => {
    if (state.multiSelectionActive) {
      const changedEdges = edges.map(edge => createSelectionChange(edge.id, true));
      state.hooks.edgesChange.trigger(changedEdges);
      return;
    }
    state.hooks.edgesChange.trigger(getSelectionChanges(edgeLookup.value, new Set(edges.map(e => e.id))));
    state.hooks.nodesChange.trigger(getSelectionChanges(nodeLookup.value, /* @__PURE__ */new Set(), true));
  };
  const addSelectedElements = elements => {
    elementSelectionHandler(elements, true);
  };
  const removeSelectedNodes = nodes => {
    const nodesToUnselect = nodes || state.nodes;
    const nodeChanges = nodesToUnselect.map(n => {
      n.selected = false;
      return createSelectionChange(n.id, false);
    });
    state.hooks.nodesChange.trigger(nodeChanges);
  };
  const removeSelectedEdges = edges => {
    const edgesToUnselect = edges || state.edges;
    const edgeChanges = edgesToUnselect.map(e => {
      e.selected = false;
      return createSelectionChange(e.id, false);
    });
    state.hooks.edgesChange.trigger(edgeChanges);
  };
  const removeSelectedElements = elements => {
    if (!elements || !elements.length) {
      return elementSelectionHandler([], false);
    }
    const changes = elements.reduce((changes2, curr) => {
      const selectionChange = createSelectionChange(curr.id, false);
      if (isNode(curr)) {
        changes2.nodes.push(selectionChange);
      } else {
        changes2.edges.push(selectionChange);
      }
      return changes2;
    }, {
      nodes: [],
      edges: []
    });
    if (changes.nodes.length) {
      state.hooks.nodesChange.trigger(changes.nodes);
    }
    if (changes.edges.length) {
      state.hooks.edgesChange.trigger(changes.edges);
    }
  };
  const setMinZoom = minZoom => {
    var _a;
    (_a = state.d3Zoom) == null ? void 0 : _a.scaleExtent([minZoom, state.maxZoom]);
    state.minZoom = minZoom;
  };
  const setMaxZoom = maxZoom => {
    var _a;
    (_a = state.d3Zoom) == null ? void 0 : _a.scaleExtent([state.minZoom, maxZoom]);
    state.maxZoom = maxZoom;
  };
  const setTranslateExtent = translateExtent => {
    var _a;
    (_a = state.d3Zoom) == null ? void 0 : _a.translateExtent(translateExtent);
    state.translateExtent = translateExtent;
  };
  const setNodeExtent = nodeExtent => {
    state.nodeExtent = nodeExtent;
    updateNodeInternals();
  };
  const setPaneClickDistance = clickDistance => {
    var _a;
    (_a = state.d3Zoom) == null ? void 0 : _a.clickDistance(clickDistance);
  };
  const setInteractive = isInteractive => {
    state.nodesDraggable = isInteractive;
    state.nodesConnectable = isInteractive;
    state.elementsSelectable = isInteractive;
  };
  const setNodes = nodes => {
    const nextNodes = nodes instanceof Function ? nodes(state.nodes) : nodes;
    if (!state.initialized && !nextNodes.length) {
      return;
    }
    state.nodes = createGraphNodes(nextNodes, findNode, state.hooks.error.trigger);
  };
  const setEdges = edges => {
    const nextEdges = edges instanceof Function ? edges(state.edges) : edges;
    if (!state.initialized && !nextEdges.length) {
      return;
    }
    const validEdges = createGraphEdges(nextEdges, state.isValidConnection, findNode, findEdge, state.hooks.error.trigger, state.defaultEdgeOptions, state.nodes, state.edges);
    updateConnectionLookup(state.connectionLookup, edgeLookup.value, validEdges);
    state.edges = validEdges;
  };
  const setElements = elements => {
    const nextElements = elements instanceof Function ? elements([...state.nodes, ...state.edges]) : elements;
    if (!state.initialized && !nextElements.length) {
      return;
    }
    setNodes(nextElements.filter(isNode));
    setEdges(nextElements.filter(isEdge));
  };
  const addNodes = nodes => {
    let nextNodes = nodes instanceof Function ? nodes(state.nodes) : nodes;
    nextNodes = Array.isArray(nextNodes) ? nextNodes : [nextNodes];
    const graphNodes = createGraphNodes(nextNodes, findNode, state.hooks.error.trigger);
    const changes = [];
    for (const node of graphNodes) {
      changes.push(createAdditionChange(node));
    }
    if (changes.length) {
      state.hooks.nodesChange.trigger(changes);
    }
  };
  const addEdges = params => {
    let nextEdges = params instanceof Function ? params(state.edges) : params;
    nextEdges = Array.isArray(nextEdges) ? nextEdges : [nextEdges];
    const validEdges = createGraphEdges(nextEdges, state.isValidConnection, findNode, findEdge, state.hooks.error.trigger, state.defaultEdgeOptions, state.nodes, state.edges);
    const changes = [];
    for (const edge of validEdges) {
      changes.push(createAdditionChange(edge));
    }
    if (changes.length) {
      state.hooks.edgesChange.trigger(changes);
    }
  };
  const removeNodes = function removeNodes(nodes, removeConnectedEdges, removeChildren) {
    if (removeConnectedEdges === void 0) {
      removeConnectedEdges = true;
    }
    if (removeChildren === void 0) {
      removeChildren = false;
    }
    const nextNodes = nodes instanceof Function ? nodes(state.nodes) : nodes;
    const nodesToRemove = Array.isArray(nextNodes) ? nextNodes : [nextNodes];
    const nodeChanges = [];
    const edgeChanges = [];
    function createEdgeRemovalChanges(nodes2) {
      const connectedEdges = getConnectedEdges$1(nodes2);
      for (const edge of connectedEdges) {
        if (isDef(edge.deletable) ? edge.deletable : true) {
          edgeChanges.push(createEdgeRemoveChange(edge.id, edge.source, edge.target, edge.sourceHandle, edge.targetHandle));
        }
      }
    }
    function createChildrenRemovalChanges(id2) {
      const children2 = [];
      for (const node of state.nodes) {
        if (node.parentNode === id2) {
          children2.push(node);
        }
      }
      if (children2.length) {
        for (const child of children2) {
          nodeChanges.push(createNodeRemoveChange(child.id));
        }
        if (removeConnectedEdges) {
          createEdgeRemovalChanges(children2);
        }
        for (const child of children2) {
          createChildrenRemovalChanges(child.id);
        }
      }
    }
    for (const item of nodesToRemove) {
      const currNode = typeof item === "string" ? findNode(item) : item;
      if (!currNode) {
        continue;
      }
      if (isDef(currNode.deletable) && !currNode.deletable) {
        continue;
      }
      nodeChanges.push(createNodeRemoveChange(currNode.id));
      if (removeConnectedEdges) {
        createEdgeRemovalChanges([currNode]);
      }
      if (removeChildren) {
        createChildrenRemovalChanges(currNode.id);
      }
    }
    if (edgeChanges.length) {
      state.hooks.edgesChange.trigger(edgeChanges);
    }
    if (nodeChanges.length) {
      state.hooks.nodesChange.trigger(nodeChanges);
    }
  };
  const removeEdges = edges => {
    const nextEdges = edges instanceof Function ? edges(state.edges) : edges;
    const edgesToRemove = Array.isArray(nextEdges) ? nextEdges : [nextEdges];
    const changes = [];
    for (const item of edgesToRemove) {
      const currEdge = typeof item === "string" ? findEdge(item) : item;
      if (!currEdge) {
        continue;
      }
      if (isDef(currEdge.deletable) && !currEdge.deletable) {
        continue;
      }
      changes.push(createEdgeRemoveChange(typeof item === "string" ? item : item.id, currEdge.source, currEdge.target, currEdge.sourceHandle, currEdge.targetHandle));
    }
    state.hooks.edgesChange.trigger(changes);
  };
  const updateEdge2 = function updateEdge2(oldEdge, newConnection, shouldReplaceId) {
    if (shouldReplaceId === void 0) {
      shouldReplaceId = true;
    }
    const prevEdge = findEdge(oldEdge.id);
    if (!prevEdge) {
      return false;
    }
    const prevEdgeIndex = state.edges.indexOf(prevEdge);
    const newEdge = updateEdgeAction(oldEdge, newConnection, prevEdge, shouldReplaceId, state.hooks.error.trigger);
    if (newEdge) {
      const _createGraphEdges = createGraphEdges([newEdge], state.isValidConnection, findNode, findEdge, state.hooks.error.trigger, state.defaultEdgeOptions, state.nodes, state.edges),
        validEdge = _createGraphEdges[0];
      state.edges = state.edges.map((edge, index) => index === prevEdgeIndex ? validEdge : edge);
      updateConnectionLookup(state.connectionLookup, edgeLookup.value, [validEdge]);
      return validEdge;
    }
    return false;
  };
  const updateEdgeData = function updateEdgeData(id2, dataUpdate, options) {
    if (options === void 0) {
      options = {
        replace: false
      };
    }
    const edge = findEdge(id2);
    if (!edge) {
      return;
    }
    const nextData = typeof dataUpdate === "function" ? dataUpdate(edge) : dataUpdate;
    edge.data = options.replace ? nextData : _extends({}, edge.data, nextData);
  };
  const applyNodeChanges2 = changes => {
    return applyChanges(changes, state.nodes);
  };
  const applyEdgeChanges2 = changes => {
    const changedEdges = applyChanges(changes, state.edges);
    updateConnectionLookup(state.connectionLookup, edgeLookup.value, changedEdges);
    return changedEdges;
  };
  const updateNode = function updateNode(id2, nodeUpdate, options) {
    if (options === void 0) {
      options = {
        replace: false
      };
    }
    const node = findNode(id2);
    if (!node) {
      return;
    }
    const nextNode = typeof nodeUpdate === "function" ? nodeUpdate(node) : nodeUpdate;
    if (options.replace) {
      state.nodes.splice(state.nodes.indexOf(node), 1, nextNode);
    } else {
      Object.assign(node, nextNode);
    }
  };
  const updateNodeData = function updateNodeData(id2, dataUpdate, options) {
    if (options === void 0) {
      options = {
        replace: false
      };
    }
    const node = findNode(id2);
    if (!node) {
      return;
    }
    const nextData = typeof dataUpdate === "function" ? dataUpdate(node) : dataUpdate;
    node.data = options.replace ? nextData : _extends({}, node.data, nextData);
  };
  const startConnection = function startConnection(startHandle, position, isClick) {
    if (isClick === void 0) {
      isClick = false;
    }
    if (isClick) {
      state.connectionClickStartHandle = startHandle;
    } else {
      state.connectionStartHandle = startHandle;
    }
    state.connectionEndHandle = null;
    state.connectionStatus = null;
    if (position) {
      state.connectionPosition = position;
    }
  };
  const updateConnection = function updateConnection(position, result, status) {
    if (result === void 0) {
      result = null;
    }
    if (status === void 0) {
      status = null;
    }
    if (state.connectionStartHandle) {
      state.connectionPosition = position;
      state.connectionEndHandle = result;
      state.connectionStatus = status;
    }
  };
  const endConnection = (event, isClick) => {
    state.connectionPosition = {
      x: Number.NaN,
      y: Number.NaN
    };
    state.connectionEndHandle = null;
    state.connectionStatus = null;
    if (isClick) {
      state.connectionClickStartHandle = null;
    } else {
      state.connectionStartHandle = null;
    }
  };
  const getNodeRect = nodeOrRect => {
    const isRectObj = isRect(nodeOrRect);
    const node = isRectObj ? null : isGraphNode(nodeOrRect) ? nodeOrRect : findNode(nodeOrRect.id);
    if (!isRectObj && !node) {
      return [null, null, isRectObj];
    }
    const nodeRect = isRectObj ? nodeOrRect : nodeToRect(node);
    return [nodeRect, node, isRectObj];
  };
  const getIntersectingNodes = function getIntersectingNodes(nodeOrRect, partially, nodes) {
    if (partially === void 0) {
      partially = true;
    }
    if (nodes === void 0) {
      nodes = state.nodes;
    }
    const _getNodeRect = getNodeRect(nodeOrRect),
      nodeRect = _getNodeRect[0],
      node = _getNodeRect[1],
      isRect2 = _getNodeRect[2];
    if (!nodeRect) {
      return [];
    }
    const intersections = [];
    for (const n of nodes || state.nodes) {
      if (!isRect2 && (n.id === node.id || !n.computedPosition)) {
        continue;
      }
      const currNodeRect = nodeToRect(n);
      const overlappingArea = getOverlappingArea(currNodeRect, nodeRect);
      const partiallyVisible = partially && overlappingArea > 0;
      if (partiallyVisible || overlappingArea >= currNodeRect.width * currNodeRect.height || overlappingArea >= Number(nodeRect.width) * Number(nodeRect.height)) {
        intersections.push(n);
      }
    }
    return intersections;
  };
  const isNodeIntersecting = function isNodeIntersecting(nodeOrRect, area, partially) {
    if (partially === void 0) {
      partially = true;
    }
    const _getNodeRect2 = getNodeRect(nodeOrRect),
      nodeRect = _getNodeRect2[0];
    if (!nodeRect) {
      return false;
    }
    const overlappingArea = getOverlappingArea(nodeRect, area);
    const partiallyVisible = partially && overlappingArea > 0;
    return partiallyVisible || overlappingArea >= Number(nodeRect.width) * Number(nodeRect.height);
  };
  const panBy = delta => {
    const viewport = state.viewport,
      dimensions = state.dimensions,
      d3Zoom = state.d3Zoom,
      d3Selection = state.d3Selection,
      translateExtent = state.translateExtent;
    if (!d3Zoom || !d3Selection || !delta.x && !delta.y) {
      return false;
    }
    const nextTransform = identity$2.translate(viewport.x + delta.x, viewport.y + delta.y).scale(viewport.zoom);
    const extent = [[0, 0], [dimensions.width, dimensions.height]];
    const constrainedTransform = d3Zoom.constrain()(nextTransform, extent, translateExtent);
    const transformChanged = state.viewport.x !== constrainedTransform.x || state.viewport.y !== constrainedTransform.y || state.viewport.zoom !== constrainedTransform.k;
    d3Zoom.transform(d3Selection, constrainedTransform);
    return transformChanged;
  };
  const setState = options => {
    const opts = options instanceof Function ? options(state) : options;
    const exclude = ["d3Zoom", "d3Selection", "d3ZoomHandler", "viewportRef", "vueFlowRef", "dimensions", "hooks"];
    if (isDef(opts.defaultEdgeOptions)) {
      state.defaultEdgeOptions = opts.defaultEdgeOptions;
    }
    const elements = opts.modelValue || opts.nodes || opts.edges ? [] : void 0;
    if (elements) {
      if (opts.modelValue) {
        elements.push(...opts.modelValue);
      }
      if (opts.nodes) {
        elements.push(...opts.nodes);
      }
      if (opts.edges) {
        elements.push(...opts.edges);
      }
      setElements(elements);
    }
    const setSkippedOptions = () => {
      if (isDef(opts.maxZoom)) {
        setMaxZoom(opts.maxZoom);
      }
      if (isDef(opts.minZoom)) {
        setMinZoom(opts.minZoom);
      }
      if (isDef(opts.translateExtent)) {
        setTranslateExtent(opts.translateExtent);
      }
    };
    for (const o of Object.keys(opts)) {
      const key = o;
      const option = opts[key];
      if (![...storeOptionsToSkip, ...exclude].includes(key) && isDef(option)) {
        state[key] = option;
      }
    }
    until(() => state.d3Zoom).not.toBeNull().then(setSkippedOptions);
    if (!state.initialized) {
      state.initialized = true;
    }
  };
  const toObject = () => {
    const nodes = [];
    const edges = [];
    for (const node of state.nodes) {
      node.computedPosition;
        node.handleBounds;
        node.selected;
        node.dimensions;
        node.isParent;
        node.resizing;
        node.dragging;
        node.events;
        const rest = _objectWithoutPropertiesLoose(node, _excluded4);
      nodes.push(rest);
    }
    for (const edge of state.edges) {
      edge.selected;
        edge.sourceNode;
        edge.targetNode;
        edge.events;
        const rest = _objectWithoutPropertiesLoose(edge, _excluded5);
      edges.push(rest);
    }
    return JSON.parse(JSON.stringify({
      nodes,
      edges,
      position: [state.viewport.x, state.viewport.y],
      zoom: state.viewport.zoom,
      viewport: state.viewport
    }));
  };
  const fromObject = obj => {
    return new Promise(resolve => {
      const nodes = obj.nodes,
        edges = obj.edges,
        position = obj.position,
        zoom2 = obj.zoom,
        viewport = obj.viewport;
      if (nodes) {
        setNodes(nodes);
      }
      if (edges) {
        setEdges(edges);
      }
      const _ref61 = (viewport == null ? void 0 : viewport.x) && (viewport == null ? void 0 : viewport.y) ? [viewport.x, viewport.y] : position != null ? position : [null, null],
        xPos = _ref61[0],
        yPos = _ref61[1];
      if (xPos && yPos) {
        const nextZoom = (viewport == null ? void 0 : viewport.zoom) || zoom2 || state.viewport.zoom;
        return until(() => viewportHelper.value.viewportInitialized).toBe(true).then(() => {
          viewportHelper.value.setViewport({
            x: xPos,
            y: yPos,
            zoom: nextZoom
          }).then(() => {
            resolve(true);
          });
        });
      } else {
        resolve(true);
      }
    });
  };
  const $reset = () => {
    const resetState = useState();
    state.edges = [];
    state.nodes = [];
    if (state.d3Zoom && state.d3Selection) {
      var _resetState$defaultVi, _resetState$defaultVi2, _resetState$defaultVi3;
      const updatedTransform = identity$2.translate((_resetState$defaultVi = resetState.defaultViewport.x) != null ? _resetState$defaultVi : 0, (_resetState$defaultVi2 = resetState.defaultViewport.y) != null ? _resetState$defaultVi2 : 0).scale(clamp((_resetState$defaultVi3 = resetState.defaultViewport.zoom) != null ? _resetState$defaultVi3 : 1, resetState.minZoom, resetState.maxZoom));
      const bbox = state.viewportRef.getBoundingClientRect();
      const extent = [[0, 0], [bbox.width, bbox.height]];
      const constrainedTransform = state.d3Zoom.constrain()(updatedTransform, extent, resetState.translateExtent);
      state.d3Zoom.transform(state.d3Selection, constrainedTransform);
    }
    setState(resetState);
  };
  return {
    updateNodePositions,
    updateNodeDimensions,
    setElements,
    setNodes,
    setEdges,
    addNodes,
    addEdges,
    removeNodes,
    removeEdges,
    findNode,
    findEdge,
    updateEdge: updateEdge2,
    updateEdgeData,
    updateNode,
    updateNodeData,
    applyEdgeChanges: applyEdgeChanges2,
    applyNodeChanges: applyNodeChanges2,
    addSelectedElements,
    addSelectedNodes,
    addSelectedEdges,
    setMinZoom,
    setMaxZoom,
    setTranslateExtent,
    setNodeExtent,
    setPaneClickDistance,
    removeSelectedElements,
    removeSelectedNodes,
    removeSelectedEdges,
    startConnection,
    updateConnection,
    endConnection,
    setInteractive,
    setState,
    getIntersectingNodes,
    getIncomers: getIncomers$1,
    getOutgoers: getOutgoers$1,
    getConnectedEdges: getConnectedEdges$1,
    getHandleConnections,
    isNodeIntersecting,
    panBy,
    fitView: params => viewportHelper.value.fitView(params),
    zoomIn: transitionOpts => viewportHelper.value.zoomIn(transitionOpts),
    zoomOut: transitionOpts => viewportHelper.value.zoomOut(transitionOpts),
    zoomTo: (zoomLevel, transitionOpts) => viewportHelper.value.zoomTo(zoomLevel, transitionOpts),
    setViewport: (params, transitionOpts) => viewportHelper.value.setViewport(params, transitionOpts),
    setTransform: (params, transitionOpts) => viewportHelper.value.setTransform(params, transitionOpts),
    getViewport: () => viewportHelper.value.getViewport(),
    getTransform: () => viewportHelper.value.getTransform(),
    setCenter: (x, y, opts) => viewportHelper.value.setCenter(x, y, opts),
    fitBounds: (params, opts) => viewportHelper.value.fitBounds(params, opts),
    project: params => viewportHelper.value.project(params),
    screenToFlowCoordinate: params => viewportHelper.value.screenToFlowCoordinate(params),
    flowToScreenCoordinate: params => viewportHelper.value.flowToScreenCoordinate(params),
    toObject,
    fromObject,
    updateNodeInternals,
    viewportHelper,
    $reset,
    $destroy: () => {}
  };
}
const _hoisted_1$9 = ["data-id", "data-handleid", "data-nodeid", "data-handlepos"];
const __default__$f = {
  name: "Handle",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$f = /* @__PURE__ */defineComponent(_extends({}, __default__$f, {
  props: {
    id: {
      default: null
    },
    type: {},
    position: {
      default: () => Position.Top
    },
    isValidConnection: {
      type: Function
    },
    connectable: {
      type: [Boolean, Number, String, Function],
      default: void 0
    },
    connectableStart: {
      type: Boolean,
      default: true
    },
    connectableEnd: {
      type: Boolean,
      default: true
    }
  },
  setup(__props, _ref62) {
    let __expose = _ref62.expose;
    const props = createPropsRestProxy(__props, ["position", "connectable", "connectableStart", "connectableEnd", "id"]);
    const type = toRef(() => {
      var _props$type;
      return (_props$type = props.type) != null ? _props$type : "source";
    });
    const isValidConnection = toRef(() => {
      var _props$isValidConnect;
      return (_props$isValidConnect = props.isValidConnection) != null ? _props$isValidConnect : null;
    });
    const _useVueFlow7 = useVueFlow(),
      flowId = _useVueFlow7.id,
      connectionStartHandle = _useVueFlow7.connectionStartHandle,
      connectionClickStartHandle = _useVueFlow7.connectionClickStartHandle,
      connectionEndHandle = _useVueFlow7.connectionEndHandle,
      vueFlowRef = _useVueFlow7.vueFlowRef,
      nodesConnectable = _useVueFlow7.nodesConnectable,
      noDragClassName = _useVueFlow7.noDragClassName,
      noPanClassName = _useVueFlow7.noPanClassName;
    const _useNode = useNode(),
      nodeId = _useNode.id,
      node = _useNode.node,
      nodeEl = _useNode.nodeEl,
      connectedEdges = _useNode.connectedEdges;
    const handle = ref();
    const isConnectableStart = toRef(() => typeof __props.connectableStart !== "undefined" ? __props.connectableStart : true);
    const isConnectableEnd = toRef(() => typeof __props.connectableEnd !== "undefined" ? __props.connectableEnd : true);
    const isConnecting = toRef(() => {
      var _a, _b, _c, _d, _e, _f;
      return ((_a = connectionStartHandle.value) == null ? void 0 : _a.nodeId) === nodeId && ((_b = connectionStartHandle.value) == null ? void 0 : _b.id) === __props.id && ((_c = connectionStartHandle.value) == null ? void 0 : _c.type) === type.value || ((_d = connectionEndHandle.value) == null ? void 0 : _d.nodeId) === nodeId && ((_e = connectionEndHandle.value) == null ? void 0 : _e.id) === __props.id && ((_f = connectionEndHandle.value) == null ? void 0 : _f.type) === type.value;
    });
    const isClickConnecting = toRef(() => {
      var _a, _b, _c;
      return ((_a = connectionClickStartHandle.value) == null ? void 0 : _a.nodeId) === nodeId && ((_b = connectionClickStartHandle.value) == null ? void 0 : _b.id) === __props.id && ((_c = connectionClickStartHandle.value) == null ? void 0 : _c.type) === type.value;
    });
    const _useHandle = useHandle({
        nodeId,
        handleId: __props.id,
        isValidConnection,
        type
      }),
      handlePointerDown = _useHandle.handlePointerDown,
      handleClick = _useHandle.handleClick;
    const isConnectable = computed(() => {
      if (typeof __props.connectable === "string" && __props.connectable === "single") {
        return !connectedEdges.value.some(edge => {
          const id2 = edge[type.value + "Handle"];
          if (edge[type.value] !== nodeId) {
            return false;
          }
          return id2 ? id2 === __props.id : true;
        });
      }
      if (typeof __props.connectable === "number") {
        return connectedEdges.value.filter(edge => {
          const id2 = edge[type.value + "Handle"];
          if (edge[type.value] !== nodeId) {
            return false;
          }
          return id2 ? id2 === __props.id : true;
        }).length < __props.connectable;
      }
      if (typeof __props.connectable === "function") {
        return __props.connectable(node, connectedEdges.value);
      }
      return isDef(__props.connectable) ? __props.connectable : nodesConnectable.value;
    });
    onMounted(() => {
      var _node$handleBounds$ty;
      var _a;
      if (!node.dimensions.width || !node.dimensions.height) {
        return;
      }
      const existingBounds = (_a = node.handleBounds[type.value]) == null ? void 0 : _a.find(b => b.id === __props.id);
      if (!vueFlowRef.value || existingBounds) {
        return;
      }
      const viewportNode = vueFlowRef.value.querySelector(".vue-flow__transformationpane");
      if (!nodeEl.value || !handle.value || !viewportNode || !__props.id) {
        return;
      }
      const nodeBounds = nodeEl.value.getBoundingClientRect();
      const handleBounds = handle.value.getBoundingClientRect();
      const style = window.getComputedStyle(viewportNode);
      const _window$DOMMatrixRead2 = new window.DOMMatrixReadOnly(style.transform),
        zoom2 = _window$DOMMatrixRead2.m22;
      const nextBounds = _extends({
        id: __props.id,
        position: __props.position,
        x: (handleBounds.left - nodeBounds.left) / zoom2,
        y: (handleBounds.top - nodeBounds.top) / zoom2,
        type: type.value,
        nodeId
      }, getDimensions(handle.value));
      node.handleBounds[type.value] = [...((_node$handleBounds$ty = node.handleBounds[type.value]) != null ? _node$handleBounds$ty : []), nextBounds];
    });
    function onPointerDown(event) {
      const isMouseTriggered = isMouseEvent(event);
      if (isConnectable.value && isConnectableStart.value && (isMouseTriggered && event.button === 0 || !isMouseTriggered)) {
        handlePointerDown(event);
      }
    }
    function onClick(event) {
      if (!nodeId || !connectionClickStartHandle.value && !isConnectableStart.value) {
        return;
      }
      if (isConnectable.value) {
        handleClick(event);
      }
    }
    __expose({
      handleClick,
      handlePointerDown,
      onClick,
      onPointerDown
    });
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        ref_key: "handle",
        ref: handle,
        "data-id": unref(flowId) + "-" + unref(nodeId) + "-" + __props.id + "-" + type.value,
        "data-handleid": __props.id,
        "data-nodeid": unref(nodeId),
        "data-handlepos": _ctx.position,
        class: normalizeClass(["vue-flow__handle", ["vue-flow__handle-" + _ctx.position, "vue-flow__handle-" + __props.id, unref(noDragClassName), unref(noPanClassName), type.value, {
          connectable: isConnectable.value,
          connecting: isClickConnecting.value,
          connectablestart: isConnectableStart.value,
          connectableend: isConnectableEnd.value,
          connectionindicator: isConnectable.value && (isConnectableStart.value && !isConnecting.value || isConnectableEnd.value && isConnecting.value)
        }]]),
        onMousedown: onPointerDown,
        onTouchstartPassive: onPointerDown,
        onClick
      }, [renderSlot(_ctx.$slots, "default", {
        id: _ctx.id
      })], 42, _hoisted_1$9);
    };
  }
}));
const DefaultNode = function DefaultNode(_ref63) {
  var _data$label;
  let _ref63$sourcePosition = _ref63.sourcePosition,
    sourcePosition = _ref63$sourcePosition === void 0 ? Position.Bottom : _ref63$sourcePosition,
    _ref63$targetPosition = _ref63.targetPosition,
    targetPosition = _ref63$targetPosition === void 0 ? Position.Top : _ref63$targetPosition,
    _label = _ref63.label,
    _ref63$connectable = _ref63.connectable,
    connectable = _ref63$connectable === void 0 ? true : _ref63$connectable,
    isValidTargetPos = _ref63.isValidTargetPos,
    isValidSourcePos = _ref63.isValidSourcePos,
    data = _ref63.data;
  const label = (_data$label = data.label) != null ? _data$label : _label;
  return [h(_sfc_main$f, {
    type: "target",
    position: targetPosition,
    connectable,
    isValidConnection: isValidTargetPos
  }), typeof label !== "string" && label ? h(label) : h(Fragment, [label]), h(_sfc_main$f, {
    type: "source",
    position: sourcePosition,
    connectable,
    isValidConnection: isValidSourcePos
  })];
};
DefaultNode.props = ["sourcePosition", "targetPosition", "label", "isValidTargetPos", "isValidSourcePos", "connectable", "data"];
DefaultNode.inheritAttrs = false;
DefaultNode.compatConfig = {
  MODE: 3
};
const DefaultNode$1 = DefaultNode;
const OutputNode = function OutputNode(_ref64) {
  var _data$label2;
  let _ref64$targetPosition = _ref64.targetPosition,
    targetPosition = _ref64$targetPosition === void 0 ? Position.Top : _ref64$targetPosition,
    _label = _ref64.label,
    _ref64$connectable = _ref64.connectable,
    connectable = _ref64$connectable === void 0 ? true : _ref64$connectable,
    isValidTargetPos = _ref64.isValidTargetPos,
    data = _ref64.data;
  const label = (_data$label2 = data.label) != null ? _data$label2 : _label;
  return [h(_sfc_main$f, {
    type: "target",
    position: targetPosition,
    connectable,
    isValidConnection: isValidTargetPos
  }), typeof label !== "string" && label ? h(label) : h(Fragment, [label])];
};
OutputNode.props = ["targetPosition", "label", "isValidTargetPos", "connectable", "data"];
OutputNode.inheritAttrs = false;
OutputNode.compatConfig = {
  MODE: 3
};
const OutputNode$1 = OutputNode;
const InputNode = function InputNode(_ref65) {
  var _data$label3;
  let _ref65$sourcePosition = _ref65.sourcePosition,
    sourcePosition = _ref65$sourcePosition === void 0 ? Position.Bottom : _ref65$sourcePosition,
    _label = _ref65.label,
    _ref65$connectable = _ref65.connectable,
    connectable = _ref65$connectable === void 0 ? true : _ref65$connectable,
    isValidSourcePos = _ref65.isValidSourcePos,
    data = _ref65.data;
  const label = (_data$label3 = data.label) != null ? _data$label3 : _label;
  return [typeof label !== "string" && label ? h(label) : h(Fragment, [label]), h(_sfc_main$f, {
    type: "source",
    position: sourcePosition,
    connectable,
    isValidConnection: isValidSourcePos
  })];
};
InputNode.props = ["sourcePosition", "label", "isValidSourcePos", "connectable", "data"];
InputNode.inheritAttrs = false;
InputNode.compatConfig = {
  MODE: 3
};
const InputNode$1 = InputNode;
const _hoisted_1$8 = ["transform"];
const _hoisted_2$2$1 = ["width", "height", "x", "y", "rx", "ry"];
const _hoisted_3$1$1 = ["y"];
const __default__$e = {
  name: "EdgeText",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$e = /* @__PURE__ */defineComponent(_extends({}, __default__$e, {
  props: {
    x: {},
    y: {},
    label: {},
    labelStyle: {
      default: () => ({})
    },
    labelShowBg: {
      type: Boolean,
      default: true
    },
    labelBgStyle: {
      default: () => ({})
    },
    labelBgPadding: {
      default: () => [2, 4]
    },
    labelBgBorderRadius: {
      default: 2
    }
  },
  setup(__props) {
    const box = ref({
      x: 0,
      y: 0,
      width: 0,
      height: 0
    });
    const el = ref(null);
    const transform = computed(() => "translate(" + (__props.x - box.value.width / 2) + " " + (__props.y - box.value.height / 2) + ")");
    onMounted(getBox);
    watch([() => __props.x, () => __props.y, el, () => __props.label], getBox);
    function getBox() {
      if (!el.value) {
        return;
      }
      const nextBox = el.value.getBBox();
      if (nextBox.width !== box.value.width || nextBox.height !== box.value.height) {
        box.value = nextBox;
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("g", {
        transform: transform.value,
        class: "vue-flow__edge-textwrapper"
      }, [_ctx.labelShowBg ? (openBlock(), createElementBlock("rect", {
        key: 0,
        class: "vue-flow__edge-textbg",
        width: box.value.width + 2 * _ctx.labelBgPadding[0] + "px",
        height: box.value.height + 2 * _ctx.labelBgPadding[1] + "px",
        x: -_ctx.labelBgPadding[0],
        y: -_ctx.labelBgPadding[1],
        style: normalizeStyle(_ctx.labelBgStyle),
        rx: _ctx.labelBgBorderRadius,
        ry: _ctx.labelBgBorderRadius
      }, null, 12, _hoisted_2$2$1)) : createCommentVNode("", true), createBaseVNode("text", mergeProps(_ctx.$attrs, {
        ref_key: "el",
        ref: el,
        class: "vue-flow__edge-text",
        y: box.value.height / 2,
        dy: "0.3em",
        style: _ctx.labelStyle
      }), [renderSlot(_ctx.$slots, "default", {}, () => [typeof _ctx.label !== "string" ? (openBlock(), createBlock(resolveDynamicComponent(_ctx.label), {
        key: 0
      })) : (openBlock(), createElementBlock(Fragment, {
        key: 1
      }, [createTextVNode(toDisplayString(_ctx.label), 1)], 64))])], 16, _hoisted_3$1$1)], 8, _hoisted_1$8);
    };
  }
}));
const _hoisted_1$7$1 = ["id", "d", "marker-end", "marker-start"];
const _hoisted_2$1$1 = ["d", "stroke-width"];
const __default__$d = {
  name: "BaseEdge",
  inheritAttrs: false,
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$d = /* @__PURE__ */defineComponent(_extends({}, __default__$d, {
  props: {
    id: {},
    labelX: {},
    labelY: {},
    path: {},
    label: {},
    markerStart: {},
    markerEnd: {},
    interactionWidth: {
      default: 20
    },
    labelStyle: {},
    labelShowBg: {
      type: Boolean
    },
    labelBgStyle: {},
    labelBgPadding: {},
    labelBgBorderRadius: {}
  },
  setup(__props, _ref66) {
    let __expose = _ref66.expose;
    const pathEl = ref(null);
    const interactionEl = ref(null);
    const labelEl = ref(null);
    const attrs = useAttrs();
    __expose({
      pathEl,
      interactionEl,
      labelEl
    });
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock(Fragment, null, [createBaseVNode("path", mergeProps(unref(attrs), {
        id: _ctx.id,
        ref_key: "pathEl",
        ref: pathEl,
        d: _ctx.path,
        class: "vue-flow__edge-path",
        "marker-end": _ctx.markerEnd,
        "marker-start": _ctx.markerStart
      }), null, 16, _hoisted_1$7$1), _ctx.interactionWidth ? (openBlock(), createElementBlock("path", {
        key: 0,
        ref_key: "interactionEl",
        ref: interactionEl,
        fill: "none",
        d: _ctx.path,
        "stroke-width": _ctx.interactionWidth,
        "stroke-opacity": 0,
        class: "vue-flow__edge-interaction"
      }, null, 8, _hoisted_2$1$1)) : createCommentVNode("", true), _ctx.label && _ctx.labelX && _ctx.labelY ? (openBlock(), createBlock(_sfc_main$e, {
        key: 1,
        ref_key: "labelEl",
        ref: labelEl,
        x: _ctx.labelX,
        y: _ctx.labelY,
        label: _ctx.label,
        "label-show-bg": _ctx.labelShowBg,
        "label-bg-style": _ctx.labelBgStyle,
        "label-bg-padding": _ctx.labelBgPadding,
        "label-bg-border-radius": _ctx.labelBgBorderRadius,
        "label-style": _ctx.labelStyle
      }, null, 8, ["x", "y", "label", "label-show-bg", "label-bg-style", "label-bg-padding", "label-bg-border-radius", "label-style"])) : createCommentVNode("", true)], 64);
    };
  }
}));
function getSimpleEdgeCenter(_ref67) {
  let sourceX = _ref67.sourceX,
    sourceY = _ref67.sourceY,
    targetX = _ref67.targetX,
    targetY = _ref67.targetY;
  const xOffset = Math.abs(targetX - sourceX) / 2;
  const centerX = targetX < sourceX ? targetX + xOffset : targetX - xOffset;
  const yOffset = Math.abs(targetY - sourceY) / 2;
  const centerY = targetY < sourceY ? targetY + yOffset : targetY - yOffset;
  return [centerX, centerY, xOffset, yOffset];
}
function getBezierEdgeCenter(_ref68) {
  let sourceX = _ref68.sourceX,
    sourceY = _ref68.sourceY,
    targetX = _ref68.targetX,
    targetY = _ref68.targetY,
    sourceControlX = _ref68.sourceControlX,
    sourceControlY = _ref68.sourceControlY,
    targetControlX = _ref68.targetControlX,
    targetControlY = _ref68.targetControlY;
  const centerX = sourceX * 0.125 + sourceControlX * 0.375 + targetControlX * 0.375 + targetX * 0.125;
  const centerY = sourceY * 0.125 + sourceControlY * 0.375 + targetControlY * 0.375 + targetY * 0.125;
  const offsetX = Math.abs(centerX - sourceX);
  const offsetY = Math.abs(centerY - sourceY);
  return [centerX, centerY, offsetX, offsetY];
}
function calculateControlOffset(distance2, curvature) {
  if (distance2 >= 0) {
    return 0.5 * distance2;
  } else {
    return curvature * 25 * Math.sqrt(-distance2);
  }
}
function getControlWithCurvature(_ref69) {
  let pos = _ref69.pos,
    x1 = _ref69.x1,
    y1 = _ref69.y1,
    x2 = _ref69.x2,
    y2 = _ref69.y2,
    c = _ref69.c;
  let ctX, ctY;
  switch (pos) {
    case Position.Left:
      ctX = x1 - calculateControlOffset(x1 - x2, c);
      ctY = y1;
      break;
    case Position.Right:
      ctX = x1 + calculateControlOffset(x2 - x1, c);
      ctY = y1;
      break;
    case Position.Top:
      ctX = x1;
      ctY = y1 - calculateControlOffset(y1 - y2, c);
      break;
    case Position.Bottom:
      ctX = x1;
      ctY = y1 + calculateControlOffset(y2 - y1, c);
      break;
  }
  return [ctX, ctY];
}
function getBezierPath(bezierPathParams) {
  const sourceX = bezierPathParams.sourceX,
    sourceY = bezierPathParams.sourceY,
    _bezierPathParams$sou = bezierPathParams.sourcePosition,
    sourcePosition = _bezierPathParams$sou === void 0 ? Position.Bottom : _bezierPathParams$sou,
    targetX = bezierPathParams.targetX,
    targetY = bezierPathParams.targetY,
    _bezierPathParams$tar = bezierPathParams.targetPosition,
    targetPosition = _bezierPathParams$tar === void 0 ? Position.Top : _bezierPathParams$tar,
    _bezierPathParams$cur = bezierPathParams.curvature,
    curvature = _bezierPathParams$cur === void 0 ? 0.25 : _bezierPathParams$cur;
  const _getControlWithCurvat = getControlWithCurvature({
      pos: sourcePosition,
      x1: sourceX,
      y1: sourceY,
      x2: targetX,
      y2: targetY,
      c: curvature
    }),
    sourceControlX = _getControlWithCurvat[0],
    sourceControlY = _getControlWithCurvat[1];
  const _getControlWithCurvat2 = getControlWithCurvature({
      pos: targetPosition,
      x1: targetX,
      y1: targetY,
      x2: sourceX,
      y2: sourceY,
      c: curvature
    }),
    targetControlX = _getControlWithCurvat2[0],
    targetControlY = _getControlWithCurvat2[1];
  const _getBezierEdgeCenter = getBezierEdgeCenter({
      sourceX,
      sourceY,
      targetX,
      targetY,
      sourceControlX,
      sourceControlY,
      targetControlX,
      targetControlY
    }),
    labelX = _getBezierEdgeCenter[0],
    labelY = _getBezierEdgeCenter[1],
    offsetX = _getBezierEdgeCenter[2],
    offsetY = _getBezierEdgeCenter[3];
  return ["M" + sourceX + "," + sourceY + " C" + sourceControlX + "," + sourceControlY + " " + targetControlX + "," + targetControlY + " " + targetX + "," + targetY, labelX, labelY, offsetX, offsetY];
}
function getControl(_ref70) {
  let pos = _ref70.pos,
    x1 = _ref70.x1,
    y1 = _ref70.y1,
    x2 = _ref70.x2,
    y2 = _ref70.y2;
  let ctX, ctY;
  switch (pos) {
    case Position.Left:
    case Position.Right:
      ctX = 0.5 * (x1 + x2);
      ctY = y1;
      break;
    case Position.Top:
    case Position.Bottom:
      ctX = x1;
      ctY = 0.5 * (y1 + y2);
      break;
  }
  return [ctX, ctY];
}
function getSimpleBezierPath(simpleBezierPathParams) {
  const sourceX = simpleBezierPathParams.sourceX,
    sourceY = simpleBezierPathParams.sourceY,
    _simpleBezierPathPara = simpleBezierPathParams.sourcePosition,
    sourcePosition = _simpleBezierPathPara === void 0 ? Position.Bottom : _simpleBezierPathPara,
    targetX = simpleBezierPathParams.targetX,
    targetY = simpleBezierPathParams.targetY,
    _simpleBezierPathPara2 = simpleBezierPathParams.targetPosition,
    targetPosition = _simpleBezierPathPara2 === void 0 ? Position.Top : _simpleBezierPathPara2;
  const _getControl = getControl({
      pos: sourcePosition,
      x1: sourceX,
      y1: sourceY,
      x2: targetX,
      y2: targetY
    }),
    sourceControlX = _getControl[0],
    sourceControlY = _getControl[1];
  const _getControl2 = getControl({
      pos: targetPosition,
      x1: targetX,
      y1: targetY,
      x2: sourceX,
      y2: sourceY
    }),
    targetControlX = _getControl2[0],
    targetControlY = _getControl2[1];
  const _getBezierEdgeCenter2 = getBezierEdgeCenter({
      sourceX,
      sourceY,
      targetX,
      targetY,
      sourceControlX,
      sourceControlY,
      targetControlX,
      targetControlY
    }),
    centerX = _getBezierEdgeCenter2[0],
    centerY = _getBezierEdgeCenter2[1],
    offsetX = _getBezierEdgeCenter2[2],
    offsetY = _getBezierEdgeCenter2[3];
  return ["M" + sourceX + "," + sourceY + " C" + sourceControlX + "," + sourceControlY + " " + targetControlX + "," + targetControlY + " " + targetX + "," + targetY, centerX, centerY, offsetX, offsetY];
}
const handleDirections = {
  [Position.Left]: {
    x: -1,
    y: 0
  },
  [Position.Right]: {
    x: 1,
    y: 0
  },
  [Position.Top]: {
    x: 0,
    y: -1
  },
  [Position.Bottom]: {
    x: 0,
    y: 1
  }
};
function getDirection(_ref71) {
  let source = _ref71.source,
    _ref71$sourcePosition = _ref71.sourcePosition,
    sourcePosition = _ref71$sourcePosition === void 0 ? Position.Bottom : _ref71$sourcePosition,
    target = _ref71.target;
  if (sourcePosition === Position.Left || sourcePosition === Position.Right) {
    return source.x < target.x ? {
      x: 1,
      y: 0
    } : {
      x: -1,
      y: 0
    };
  }
  return source.y < target.y ? {
    x: 0,
    y: 1
  } : {
    x: 0,
    y: -1
  };
}
function distance(a, b) {
  return Math.sqrt((b.x - a.x) ** 2 + (b.y - a.y) ** 2);
}
function getPoints(_ref72) {
  let source = _ref72.source,
    _ref72$sourcePosition = _ref72.sourcePosition,
    sourcePosition = _ref72$sourcePosition === void 0 ? Position.Bottom : _ref72$sourcePosition,
    target = _ref72.target,
    _ref72$targetPosition = _ref72.targetPosition,
    targetPosition = _ref72$targetPosition === void 0 ? Position.Top : _ref72$targetPosition,
    center = _ref72.center,
    offset = _ref72.offset;
  const sourceDir = handleDirections[sourcePosition];
  const targetDir = handleDirections[targetPosition];
  const sourceGapped = {
    x: source.x + sourceDir.x * offset,
    y: source.y + sourceDir.y * offset
  };
  const targetGapped = {
    x: target.x + targetDir.x * offset,
    y: target.y + targetDir.y * offset
  };
  const dir = getDirection({
    source: sourceGapped,
    sourcePosition,
    target: targetGapped
  });
  const dirAccessor = dir.x !== 0 ? "x" : "y";
  const currDir = dir[dirAccessor];
  let points;
  let centerX, centerY;
  const sourceGapOffset = {
    x: 0,
    y: 0
  };
  const targetGapOffset = {
    x: 0,
    y: 0
  };
  const _getSimpleEdgeCenter = getSimpleEdgeCenter({
      sourceX: source.x,
      sourceY: source.y,
      targetX: target.x,
      targetY: target.y
    }),
    defaultCenterX = _getSimpleEdgeCenter[0],
    defaultCenterY = _getSimpleEdgeCenter[1],
    defaultOffsetX = _getSimpleEdgeCenter[2],
    defaultOffsetY = _getSimpleEdgeCenter[3];
  if (sourceDir[dirAccessor] * targetDir[dirAccessor] === -1) {
    var _center$x, _center$y;
    centerX = (_center$x = center.x) != null ? _center$x : defaultCenterX;
    centerY = (_center$y = center.y) != null ? _center$y : defaultCenterY;
    const verticalSplit = [{
      x: centerX,
      y: sourceGapped.y
    }, {
      x: centerX,
      y: targetGapped.y
    }];
    const horizontalSplit = [{
      x: sourceGapped.x,
      y: centerY
    }, {
      x: targetGapped.x,
      y: centerY
    }];
    if (sourceDir[dirAccessor] === currDir) {
      points = dirAccessor === "x" ? verticalSplit : horizontalSplit;
    } else {
      points = dirAccessor === "x" ? horizontalSplit : verticalSplit;
    }
  } else {
    const sourceTarget = [{
      x: sourceGapped.x,
      y: targetGapped.y
    }];
    const targetSource = [{
      x: targetGapped.x,
      y: sourceGapped.y
    }];
    if (dirAccessor === "x") {
      points = sourceDir.x === currDir ? targetSource : sourceTarget;
    } else {
      points = sourceDir.y === currDir ? sourceTarget : targetSource;
    }
    if (sourcePosition === targetPosition) {
      const diff = Math.abs(source[dirAccessor] - target[dirAccessor]);
      if (diff <= offset) {
        const gapOffset = Math.min(offset - 1, offset - diff);
        if (sourceDir[dirAccessor] === currDir) {
          sourceGapOffset[dirAccessor] = (sourceGapped[dirAccessor] > source[dirAccessor] ? -1 : 1) * gapOffset;
        } else {
          targetGapOffset[dirAccessor] = (targetGapped[dirAccessor] > target[dirAccessor] ? -1 : 1) * gapOffset;
        }
      }
    }
    if (sourcePosition !== targetPosition) {
      const dirAccessorOpposite = dirAccessor === "x" ? "y" : "x";
      const isSameDir = sourceDir[dirAccessor] === targetDir[dirAccessorOpposite];
      const sourceGtTargetOppo = sourceGapped[dirAccessorOpposite] > targetGapped[dirAccessorOpposite];
      const sourceLtTargetOppo = sourceGapped[dirAccessorOpposite] < targetGapped[dirAccessorOpposite];
      const flipSourceTarget = sourceDir[dirAccessor] === 1 && (!isSameDir && sourceGtTargetOppo || isSameDir && sourceLtTargetOppo) || sourceDir[dirAccessor] !== 1 && (!isSameDir && sourceLtTargetOppo || isSameDir && sourceGtTargetOppo);
      if (flipSourceTarget) {
        points = dirAccessor === "x" ? sourceTarget : targetSource;
      }
    }
    const sourceGapPoint = {
      x: sourceGapped.x + sourceGapOffset.x,
      y: sourceGapped.y + sourceGapOffset.y
    };
    const targetGapPoint = {
      x: targetGapped.x + targetGapOffset.x,
      y: targetGapped.y + targetGapOffset.y
    };
    const maxXDistance = Math.max(Math.abs(sourceGapPoint.x - points[0].x), Math.abs(targetGapPoint.x - points[0].x));
    const maxYDistance = Math.max(Math.abs(sourceGapPoint.y - points[0].y), Math.abs(targetGapPoint.y - points[0].y));
    if (maxXDistance >= maxYDistance) {
      centerX = (sourceGapPoint.x + targetGapPoint.x) / 2;
      centerY = points[0].y;
    } else {
      centerX = points[0].x;
      centerY = (sourceGapPoint.y + targetGapPoint.y) / 2;
    }
  }
  const pathPoints = [source, {
    x: sourceGapped.x + sourceGapOffset.x,
    y: sourceGapped.y + sourceGapOffset.y
  }, ...points, {
    x: targetGapped.x + targetGapOffset.x,
    y: targetGapped.y + targetGapOffset.y
  }, target];
  return [pathPoints, centerX, centerY, defaultOffsetX, defaultOffsetY];
}
function getBend(a, b, c, size) {
  const bendSize = Math.min(distance(a, b) / 2, distance(b, c) / 2, size);
  const x = b.x,
    y = b.y;
  if (a.x === x && x === c.x || a.y === y && y === c.y) {
    return "L" + x + " " + y;
  }
  if (a.y === y) {
    const xDir2 = a.x < c.x ? -1 : 1;
    const yDir2 = a.y < c.y ? 1 : -1;
    return "L " + (x + bendSize * xDir2) + "," + y + "Q " + x + "," + y + " " + x + "," + (y + bendSize * yDir2);
  }
  const xDir = a.x < c.x ? 1 : -1;
  const yDir = a.y < c.y ? -1 : 1;
  return "L " + x + "," + (y + bendSize * yDir) + "Q " + x + "," + y + " " + (x + bendSize * xDir) + "," + y;
}
function getSmoothStepPath(smoothStepPathParams) {
  const sourceX = smoothStepPathParams.sourceX,
    sourceY = smoothStepPathParams.sourceY,
    _smoothStepPathParams = smoothStepPathParams.sourcePosition,
    sourcePosition = _smoothStepPathParams === void 0 ? Position.Bottom : _smoothStepPathParams,
    targetX = smoothStepPathParams.targetX,
    targetY = smoothStepPathParams.targetY,
    _smoothStepPathParams2 = smoothStepPathParams.targetPosition,
    targetPosition = _smoothStepPathParams2 === void 0 ? Position.Top : _smoothStepPathParams2,
    _smoothStepPathParams3 = smoothStepPathParams.borderRadius,
    borderRadius = _smoothStepPathParams3 === void 0 ? 5 : _smoothStepPathParams3,
    centerX = smoothStepPathParams.centerX,
    centerY = smoothStepPathParams.centerY,
    _smoothStepPathParams4 = smoothStepPathParams.offset,
    offset = _smoothStepPathParams4 === void 0 ? 20 : _smoothStepPathParams4;
  const _getPoints = getPoints({
      source: {
        x: sourceX,
        y: sourceY
      },
      sourcePosition,
      target: {
        x: targetX,
        y: targetY
      },
      targetPosition,
      center: {
        x: centerX,
        y: centerY
      },
      offset
    }),
    points = _getPoints[0],
    labelX = _getPoints[1],
    labelY = _getPoints[2],
    offsetX = _getPoints[3],
    offsetY = _getPoints[4];
  const path = points.reduce((res, p, i) => {
    let segment;
    if (i > 0 && i < points.length - 1) {
      segment = getBend(points[i - 1], p, points[i + 1], borderRadius);
    } else {
      segment = "" + (i === 0 ? "M" : "L") + p.x + " " + p.y;
    }
    res += segment;
    return res;
  }, "");
  return [path, labelX, labelY, offsetX, offsetY];
}
function getStraightPath(straightEdgeParams) {
  const sourceX = straightEdgeParams.sourceX,
    sourceY = straightEdgeParams.sourceY,
    targetX = straightEdgeParams.targetX,
    targetY = straightEdgeParams.targetY;
  const _getSimpleEdgeCenter2 = getSimpleEdgeCenter({
      sourceX,
      sourceY,
      targetX,
      targetY
    }),
    centerX = _getSimpleEdgeCenter2[0],
    centerY = _getSimpleEdgeCenter2[1],
    offsetX = _getSimpleEdgeCenter2[2],
    offsetY = _getSimpleEdgeCenter2[3];
  return ["M " + sourceX + "," + sourceY + "L " + targetX + "," + targetY, centerX, centerY, offsetX, offsetY];
}
const StraightEdge = defineComponent({
  name: "StraightEdge",
  props: ["label", "labelStyle", "labelShowBg", "labelBgStyle", "labelBgPadding", "labelBgBorderRadius", "sourceY", "sourceX", "targetX", "targetY", "markerEnd", "markerStart", "interactionWidth"],
  compatConfig: {
    MODE: 3
  },
  setup(props, _ref73) {
    let attrs = _ref73.attrs;
    return () => {
      const _getStraightPath = getStraightPath(props),
        path = _getStraightPath[0],
        labelX = _getStraightPath[1],
        labelY = _getStraightPath[2];
      return h(_sfc_main$d, _extends({
        path,
        labelX,
        labelY
      }, attrs, props));
    };
  }
});
const StraightEdge$1 = StraightEdge;
const SmoothStepEdge = defineComponent({
  name: "SmoothStepEdge",
  props: ["sourcePosition", "targetPosition", "label", "labelStyle", "labelShowBg", "labelBgStyle", "labelBgPadding", "labelBgBorderRadius", "sourceY", "sourceX", "targetX", "targetY", "borderRadius", "markerEnd", "markerStart", "interactionWidth", "offset"],
  compatConfig: {
    MODE: 3
  },
  setup(props, _ref74) {
    let attrs = _ref74.attrs;
    return () => {
      var _props$sourcePosition, _props$targetPosition;
      const _getSmoothStepPath = getSmoothStepPath(_extends({}, props, {
          sourcePosition: (_props$sourcePosition = props.sourcePosition) != null ? _props$sourcePosition : Position.Bottom,
          targetPosition: (_props$targetPosition = props.targetPosition) != null ? _props$targetPosition : Position.Top
        })),
        path = _getSmoothStepPath[0],
        labelX = _getSmoothStepPath[1],
        labelY = _getSmoothStepPath[2];
      return h(_sfc_main$d, _extends({
        path,
        labelX,
        labelY
      }, attrs, props));
    };
  }
});
const SmoothStepEdge$1 = SmoothStepEdge;
const StepEdge = defineComponent({
  name: "StepEdge",
  props: ["sourcePosition", "targetPosition", "label", "labelStyle", "labelShowBg", "labelBgStyle", "labelBgPadding", "labelBgBorderRadius", "sourceY", "sourceX", "targetX", "targetY", "markerEnd", "markerStart", "interactionWidth"],
  setup(props, _ref75) {
    let attrs = _ref75.attrs;
    return () => h(SmoothStepEdge$1, _extends({}, props, attrs, {
      borderRadius: 0
    }));
  }
});
const StepEdge$1 = StepEdge;
const BezierEdge = defineComponent({
  name: "BezierEdge",
  props: ["sourcePosition", "targetPosition", "label", "labelStyle", "labelShowBg", "labelBgStyle", "labelBgPadding", "labelBgBorderRadius", "sourceY", "sourceX", "targetX", "targetY", "curvature", "markerEnd", "markerStart", "interactionWidth"],
  compatConfig: {
    MODE: 3
  },
  setup(props, _ref76) {
    let attrs = _ref76.attrs;
    return () => {
      var _props$sourcePosition2, _props$targetPosition2;
      const _getBezierPath = getBezierPath(_extends({}, props, {
          sourcePosition: (_props$sourcePosition2 = props.sourcePosition) != null ? _props$sourcePosition2 : Position.Bottom,
          targetPosition: (_props$targetPosition2 = props.targetPosition) != null ? _props$targetPosition2 : Position.Top
        })),
        path = _getBezierPath[0],
        labelX = _getBezierPath[1],
        labelY = _getBezierPath[2];
      return h(_sfc_main$d, _extends({
        path,
        labelX,
        labelY
      }, attrs, props));
    };
  }
});
const BezierEdge$1 = BezierEdge;
const SimpleBezierEdge = defineComponent({
  name: "SimpleBezierEdge",
  props: ["sourcePosition", "targetPosition", "label", "labelStyle", "labelShowBg", "labelBgStyle", "labelBgPadding", "labelBgBorderRadius", "sourceY", "sourceX", "targetX", "targetY", "markerEnd", "markerStart", "interactionWidth"],
  compatConfig: {
    MODE: 3
  },
  setup(props, _ref77) {
    let attrs = _ref77.attrs;
    return () => {
      var _props$sourcePosition3, _props$targetPosition3;
      const _getSimpleBezierPath = getSimpleBezierPath(_extends({}, props, {
          sourcePosition: (_props$sourcePosition3 = props.sourcePosition) != null ? _props$sourcePosition3 : Position.Bottom,
          targetPosition: (_props$targetPosition3 = props.targetPosition) != null ? _props$targetPosition3 : Position.Top
        })),
        path = _getSimpleBezierPath[0],
        labelX = _getSimpleBezierPath[1],
        labelY = _getSimpleBezierPath[2];
      return h(_sfc_main$d, _extends({
        path,
        labelX,
        labelY
      }, attrs, props));
    };
  }
});
const SimpleBezierEdge$1 = SimpleBezierEdge;
const defaultNodeTypes = {
  input: InputNode$1,
  default: DefaultNode$1,
  output: OutputNode$1
};
const defaultEdgeTypes = {
  default: BezierEdge$1,
  straight: StraightEdge$1,
  step: StepEdge$1,
  smoothstep: SmoothStepEdge$1,
  simplebezier: SimpleBezierEdge$1
};
function useGetters(state, nodeLookup, edgeLookup) {
  const getNode = computed(() => id2 => nodeLookup.value.get(id2));
  const getEdge = computed(() => id2 => edgeLookup.value.get(id2));
  const getEdgeTypes = computed(() => {
    const edgeTypes = _extends({}, defaultEdgeTypes, state.edgeTypes);
    const keys = Object.keys(edgeTypes);
    for (const e of state.edges) {
      e.type && !keys.includes(e.type) && (edgeTypes[e.type] = e.type);
    }
    return edgeTypes;
  });
  const getNodeTypes = computed(() => {
    const nodeTypes = _extends({}, defaultNodeTypes, state.nodeTypes);
    const keys = Object.keys(nodeTypes);
    for (const n of state.nodes) {
      n.type && !keys.includes(n.type) && (nodeTypes[n.type] = n.type);
    }
    return nodeTypes;
  });
  const getNodes = computed(() => {
    if (state.onlyRenderVisibleElements) {
      return getNodesInside(state.nodes, {
        x: 0,
        y: 0,
        width: state.dimensions.width,
        height: state.dimensions.height
      }, state.viewport, true);
    }
    return state.nodes;
  });
  const getEdges = computed(() => {
    if (state.onlyRenderVisibleElements) {
      const visibleEdges = [];
      for (const edge of state.edges) {
        const source = nodeLookup.value.get(edge.source);
        const target = nodeLookup.value.get(edge.target);
        if (isEdgeVisible({
          sourcePos: source.computedPosition || {
            x: 0,
            y: 0
          },
          targetPos: target.computedPosition || {
            x: 0,
            y: 0
          },
          sourceWidth: source.dimensions.width,
          sourceHeight: source.dimensions.height,
          targetWidth: target.dimensions.width,
          targetHeight: target.dimensions.height,
          width: state.dimensions.width,
          height: state.dimensions.height,
          viewport: state.viewport
        })) {
          visibleEdges.push(edge);
        }
      }
      return visibleEdges;
    }
    return state.edges;
  });
  const getElements = computed(() => [...getNodes.value, ...getEdges.value]);
  const getSelectedNodes = computed(() => {
    const selectedNodes = [];
    for (const node of state.nodes) {
      if (node.selected) {
        selectedNodes.push(node);
      }
    }
    return selectedNodes;
  });
  const getSelectedEdges = computed(() => {
    const selectedEdges = [];
    for (const edge of state.edges) {
      if (edge.selected) {
        selectedEdges.push(edge);
      }
    }
    return selectedEdges;
  });
  const getSelectedElements = computed(() => [...getSelectedNodes.value, ...getSelectedEdges.value]);
  const getNodesInitialized = computed(() => {
    const initializedNodes = [];
    for (const node of state.nodes) {
      if (!!node.dimensions.width && !!node.dimensions.height && node.handleBounds !== void 0) {
        initializedNodes.push(node);
      }
    }
    return initializedNodes;
  });
  const areNodesInitialized = computed(() => getNodes.value.length > 0 && getNodesInitialized.value.length === getNodes.value.length);
  return {
    getNode,
    getEdge,
    getElements,
    getEdgeTypes,
    getNodeTypes,
    getEdges,
    getNodes,
    getSelectedElements,
    getSelectedNodes,
    getSelectedEdges,
    getNodesInitialized,
    areNodesInitialized
  };
}
class Storage {
  constructor() {
    this.currentId = 0;
    this.flows = /* @__PURE__ */new Map();
  }
  static getInstance() {
    var _ref78;
    var _a;
    const vueApp = (_a = getCurrentInstance()) == null ? void 0 : _a.appContext.app;
    const existingInstance = (_ref78 = vueApp == null ? void 0 : vueApp.config.globalProperties.$vueFlowStorage) != null ? _ref78 : Storage.instance;
    Storage.instance = existingInstance != null ? existingInstance : new Storage();
    if (vueApp) {
      vueApp.config.globalProperties.$vueFlowStorage = Storage.instance;
    }
    return Storage.instance;
  }
  set(id2, flow) {
    return this.flows.set(id2, flow);
  }
  get(id2) {
    return this.flows.get(id2);
  }
  remove(id2) {
    return this.flows.delete(id2);
  }
  create(id2, preloadedState) {
    const state = useState();
    const reactiveState = reactive(state);
    const hooksOn = {};
    for (const _ref79 of Object.entries(reactiveState.hooks)) {
      const n = _ref79[0];
      const h2 = _ref79[1];
      const name = "on" + (n.charAt(0).toUpperCase() + n.slice(1));
      hooksOn[name] = h2.on;
    }
    const emits = {};
    for (const _ref80 of Object.entries(reactiveState.hooks)) {
      const n = _ref80[0];
      const h2 = _ref80[1];
      emits[n] = h2.trigger;
    }
    const nodeLookup = computed(() => {
      const nodesMap = /* @__PURE__ */new Map();
      for (const node of reactiveState.nodes) {
        nodesMap.set(node.id, node);
      }
      return nodesMap;
    });
    const edgeLookup = computed(() => {
      const edgesMap = /* @__PURE__ */new Map();
      for (const edge of reactiveState.edges) {
        edgesMap.set(edge.id, edge);
      }
      return edgesMap;
    });
    const getters = useGetters(reactiveState, nodeLookup, edgeLookup);
    const actions = useActions(reactiveState, nodeLookup, edgeLookup);
    actions.setState(_extends({}, reactiveState, preloadedState));
    const flow = _extends({}, hooksOn, getters, actions, toRefs(reactiveState), {
      nodeLookup,
      edgeLookup,
      emits,
      id: id2,
      vueFlowVersion: "1.48.2",
      $destroy: () => {
        this.remove(id2);
      }
    });
    this.set(id2, flow);
    return flow;
  }
  getId() {
    return "vue-flow-" + this.currentId++;
  }
}
function useVueFlow(idOrOpts) {
  const storage = Storage.getInstance();
  const scope = getCurrentScope();
  const isOptsObj = typeof idOrOpts === "object";
  const options = isOptsObj ? idOrOpts : {
    id: idOrOpts
  };
  const id2 = options.id;
  const vueFlowId = id2 != null ? id2 : scope == null ? void 0 : scope.vueFlowId;
  let vueFlow;
  if (scope) {
    const injectedState = inject(VueFlow, null);
    if (typeof injectedState !== "undefined" && injectedState !== null && (!vueFlowId || injectedState.id === vueFlowId)) {
      vueFlow = injectedState;
    }
  }
  if (!vueFlow) {
    if (vueFlowId) {
      vueFlow = storage.get(vueFlowId);
    }
  }
  if (!vueFlow || vueFlowId && vueFlow.id !== vueFlowId) {
    const name = id2 != null ? id2 : storage.getId();
    const state = storage.create(name, options);
    vueFlow = state;
    const vfScope = scope != null ? scope : effectScope(true);
    vfScope.run(() => {
      watch(state.applyDefault, (shouldApplyDefault, __, onCleanup) => {
        const nodesChangeHandler = changes => {
          state.applyNodeChanges(changes);
        };
        const edgesChangeHandler = changes => {
          state.applyEdgeChanges(changes);
        };
        if (shouldApplyDefault) {
          state.onNodesChange(nodesChangeHandler);
          state.onEdgesChange(edgesChangeHandler);
        } else {
          state.hooks.value.nodesChange.off(nodesChangeHandler);
          state.hooks.value.edgesChange.off(edgesChangeHandler);
        }
        onCleanup(() => {
          state.hooks.value.nodesChange.off(nodesChangeHandler);
          state.hooks.value.edgesChange.off(edgesChangeHandler);
        });
      }, {
        immediate: true
      });
      tryOnScopeDispose(() => {
        if (vueFlow) {
          const storedInstance = storage.get(vueFlow.id);
          if (storedInstance) {
            storedInstance.$destroy();
          } else {
            warn("No store instance found for id " + vueFlow.id + " in storage.");
          }
        }
      });
    });
  } else {
    if (isOptsObj) {
      vueFlow.setState(options);
    }
  }
  if (scope) {
    provide(VueFlow, vueFlow);
    scope.vueFlowId = vueFlow.id;
  }
  if (isOptsObj) {
    const instance = getCurrentInstance();
    if ((instance == null ? void 0 : instance.type.name) !== "VueFlow") {
      vueFlow.emits.error(new VueFlowError(ErrorCode.USEVUEFLOW_OPTIONS));
    }
  }
  return vueFlow;
}
function useResizeHandler(viewportEl) {
  const _useVueFlow8 = useVueFlow(),
    emits = _useVueFlow8.emits,
    dimensions = _useVueFlow8.dimensions;
  let resizeObserver;
  onMounted(() => {
    const updateDimensions = () => {
      var _ref81;
      var _a, _b;
      if (!viewportEl.value || !((_ref81 = (_b = (_a = viewportEl.value).checkVisibility) == null ? void 0 : _b.call(_a)) != null ? _ref81 : true)) {
        return;
      }
      const size = getDimensions(viewportEl.value);
      if (size.width === 0 || size.height === 0) {
        emits.error(new VueFlowError(ErrorCode.MISSING_VIEWPORT_DIMENSIONS));
      }
      dimensions.value = {
        width: size.width || 500,
        height: size.height || 500
      };
    };
    updateDimensions();
    window.addEventListener("resize", updateDimensions);
    if (viewportEl.value) {
      resizeObserver = new ResizeObserver(() => updateDimensions());
      resizeObserver.observe(viewportEl.value);
    }
    onBeforeUnmount(() => {
      window.removeEventListener("resize", updateDimensions);
      if (resizeObserver && viewportEl.value) {
        resizeObserver.unobserve(viewportEl.value);
      }
    });
  });
}
const __default__$c = {
  name: "UserSelection",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$c = /* @__PURE__ */defineComponent(_extends({}, __default__$c, {
  props: {
    userSelectionRect: {}
  },
  setup(__props) {
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: "vue-flow__selection vue-flow__container",
        style: normalizeStyle({
          width: _ctx.userSelectionRect.width + "px",
          height: _ctx.userSelectionRect.height + "px",
          transform: "translate(" + _ctx.userSelectionRect.x + "px, " + _ctx.userSelectionRect.y + "px)"
        })
      }, null, 4);
    };
  }
}));
const _hoisted_1$6$1 = ["tabIndex"];
const __default__$b = {
  name: "NodesSelection",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$b = /* @__PURE__ */defineComponent(_extends({}, __default__$b, {
  setup(__props) {
    const _useVueFlow9 = useVueFlow(),
      emits = _useVueFlow9.emits,
      viewport = _useVueFlow9.viewport,
      getSelectedNodes = _useVueFlow9.getSelectedNodes,
      noPanClassName = _useVueFlow9.noPanClassName,
      disableKeyboardA11y = _useVueFlow9.disableKeyboardA11y,
      userSelectionActive = _useVueFlow9.userSelectionActive;
    const updatePositions = useUpdateNodePositions();
    const el = ref(null);
    const dragging = useDrag({
      el,
      onStart(args) {
        emits.selectionDragStart(args);
        emits.nodeDragStart(args);
      },
      onDrag(args) {
        emits.selectionDrag(args);
        emits.nodeDrag(args);
      },
      onStop(args) {
        emits.selectionDragStop(args);
        emits.nodeDragStop(args);
      }
    });
    onMounted(() => {
      var _a;
      if (!disableKeyboardA11y.value) {
        (_a = el.value) == null ? void 0 : _a.focus({
          preventScroll: true
        });
      }
    });
    const selectedNodesBBox = computed(() => getRectOfNodes(getSelectedNodes.value));
    const innerStyle = computed(() => ({
      width: selectedNodesBBox.value.width + "px",
      height: selectedNodesBBox.value.height + "px",
      top: selectedNodesBBox.value.y + "px",
      left: selectedNodesBBox.value.x + "px"
    }));
    function onContextMenu(event) {
      emits.selectionContextMenu({
        event,
        nodes: getSelectedNodes.value
      });
    }
    function onKeyDown(event) {
      if (disableKeyboardA11y.value) {
        return;
      }
      if (arrowKeyDiffs[event.key]) {
        event.preventDefault();
        updatePositions({
          x: arrowKeyDiffs[event.key].x,
          y: arrowKeyDiffs[event.key].y
        }, event.shiftKey);
      }
    }
    return (_ctx, _cache) => {
      return !unref(userSelectionActive) && selectedNodesBBox.value.width && selectedNodesBBox.value.height ? (openBlock(), createElementBlock("div", {
        key: 0,
        class: normalizeClass(["vue-flow__nodesselection vue-flow__container", unref(noPanClassName)]),
        style: normalizeStyle({
          transform: "translate(" + unref(viewport).x + "px," + unref(viewport).y + "px) scale(" + unref(viewport).zoom + ")"
        })
      }, [createBaseVNode("div", {
        ref_key: "el",
        ref: el,
        class: normalizeClass([{
          dragging: unref(dragging)
        }, "vue-flow__nodesselection-rect"]),
        style: normalizeStyle(innerStyle.value),
        tabIndex: unref(disableKeyboardA11y) ? void 0 : -1,
        onContextmenu: onContextMenu,
        onKeydown: onKeyDown
      }, null, 46, _hoisted_1$6$1)], 6)) : createCommentVNode("", true);
    };
  }
}));
function getMousePosition(event, containerBounds) {
  return {
    x: event.clientX - containerBounds.left,
    y: event.clientY - containerBounds.top
  };
}
const __default__$a = {
  name: "Pane",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$a = /* @__PURE__ */defineComponent(_extends({}, __default__$a, {
  props: {
    isSelecting: {
      type: Boolean
    },
    selectionKeyPressed: {
      type: Boolean
    }
  },
  setup(__props) {
    const _useVueFlow0 = useVueFlow(),
      vueFlowRef = _useVueFlow0.vueFlowRef,
      nodes = _useVueFlow0.nodes,
      viewport = _useVueFlow0.viewport,
      emits = _useVueFlow0.emits,
      userSelectionActive = _useVueFlow0.userSelectionActive,
      removeSelectedElements = _useVueFlow0.removeSelectedElements,
      userSelectionRect = _useVueFlow0.userSelectionRect,
      elementsSelectable = _useVueFlow0.elementsSelectable,
      nodesSelectionActive = _useVueFlow0.nodesSelectionActive,
      getSelectedEdges = _useVueFlow0.getSelectedEdges,
      getSelectedNodes = _useVueFlow0.getSelectedNodes,
      removeNodes = _useVueFlow0.removeNodes,
      removeEdges = _useVueFlow0.removeEdges,
      selectionMode = _useVueFlow0.selectionMode,
      deleteKeyCode = _useVueFlow0.deleteKeyCode,
      multiSelectionKeyCode = _useVueFlow0.multiSelectionKeyCode,
      multiSelectionActive = _useVueFlow0.multiSelectionActive,
      edgeLookup = _useVueFlow0.edgeLookup,
      nodeLookup = _useVueFlow0.nodeLookup,
      connectionLookup = _useVueFlow0.connectionLookup,
      defaultEdgeOptions = _useVueFlow0.defaultEdgeOptions,
      connectionStartHandle = _useVueFlow0.connectionStartHandle,
      panOnDrag = _useVueFlow0.panOnDrag;
    const container = shallowRef(null);
    const selectedNodeIds = shallowRef(/* @__PURE__ */new Set());
    const selectedEdgeIds = shallowRef(/* @__PURE__ */new Set());
    const containerBounds = shallowRef(null);
    const hasActiveSelection = toRef(() => elementsSelectable.value && (__props.isSelecting || userSelectionActive.value));
    const connectionInProgress = toRef(() => connectionStartHandle.value !== null);
    let selectionInProgress = false;
    let selectionStarted = false;
    const deleteKeyPressed = useKeyPress(deleteKeyCode, {
      actInsideInputWithModifier: false
    });
    const multiSelectKeyPressed = useKeyPress(multiSelectionKeyCode);
    watch(deleteKeyPressed, isKeyPressed => {
      if (!isKeyPressed) {
        return;
      }
      removeNodes(getSelectedNodes.value);
      removeEdges(getSelectedEdges.value);
      nodesSelectionActive.value = false;
    });
    watch(multiSelectKeyPressed, isKeyPressed => {
      multiSelectionActive.value = isKeyPressed;
    });
    function wrapHandler(handler, containerRef) {
      return event => {
        if (event.target !== containerRef) {
          return;
        }
        handler == null ? void 0 : handler(event);
      };
    }
    function onClick(event) {
      if (selectionInProgress || connectionInProgress.value) {
        selectionInProgress = false;
        return;
      }
      emits.paneClick(event);
      removeSelectedElements();
      nodesSelectionActive.value = false;
    }
    function onContextMenu(event) {
      var _a;
      if (Array.isArray(panOnDrag.value) && ((_a = panOnDrag.value) == null ? void 0 : _a.includes(2))) {
        event.preventDefault();
        return;
      }
      emits.paneContextMenu(event);
    }
    function onWheel(event) {
      emits.paneScroll(event);
    }
    function onPointerDown(event) {
      var _ref82;
      var _a, _b, _c;
      containerBounds.value = (_ref82 = (_a = vueFlowRef.value) == null ? void 0 : _a.getBoundingClientRect()) != null ? _ref82 : null;
      if (!elementsSelectable.value || !__props.isSelecting || event.button !== 0 || event.target !== container.value || !containerBounds.value) {
        return;
      }
      (_c = (_b = event.target) == null ? void 0 : _b.setPointerCapture) == null ? void 0 : _c.call(_b, event.pointerId);
      const _getMousePosition = getMousePosition(event, containerBounds.value),
        x = _getMousePosition.x,
        y = _getMousePosition.y;
      selectionStarted = true;
      selectionInProgress = false;
      removeSelectedElements();
      userSelectionRect.value = {
        width: 0,
        height: 0,
        startX: x,
        startY: y,
        x,
        y
      };
      emits.selectionStart(event);
    }
    function onPointerMove(event) {
      var _ref83;
      var _a;
      if (!containerBounds.value || !userSelectionRect.value) {
        return;
      }
      selectionInProgress = true;
      const _getEventPosition4 = getEventPosition(event, containerBounds.value),
        mouseX = _getEventPosition4.x,
        mouseY = _getEventPosition4.y;
      const _userSelectionRect$va = userSelectionRect.value,
        _userSelectionRect$va2 = _userSelectionRect$va.startX,
        startX = _userSelectionRect$va2 === void 0 ? 0 : _userSelectionRect$va2,
        _userSelectionRect$va3 = _userSelectionRect$va.startY,
        startY = _userSelectionRect$va3 === void 0 ? 0 : _userSelectionRect$va3;
      const nextUserSelectRect = {
        startX,
        startY,
        x: mouseX < startX ? mouseX : startX,
        y: mouseY < startY ? mouseY : startY,
        width: Math.abs(mouseX - startX),
        height: Math.abs(mouseY - startY)
      };
      const prevSelectedNodeIds = selectedNodeIds.value;
      const prevSelectedEdgeIds = selectedEdgeIds.value;
      selectedNodeIds.value = new Set(getNodesInside(nodes.value, nextUserSelectRect, viewport.value, selectionMode.value === SelectionMode.Partial, true).map(node => node.id));
      selectedEdgeIds.value = /* @__PURE__ */new Set();
      const edgesSelectable = (_ref83 = (_a = defaultEdgeOptions.value) == null ? void 0 : _a.selectable) != null ? _ref83 : true;
      for (const nodeId of selectedNodeIds.value) {
        const connections = connectionLookup.value.get(nodeId);
        if (!connections) {
          continue;
        }
        for (const _ref84 of connections.values()) {
          var _edge$selectable2;
          const edgeId = _ref84.edgeId;
          const edge = edgeLookup.value.get(edgeId);
          if (edge && ((_edge$selectable2 = edge.selectable) != null ? _edge$selectable2 : edgesSelectable)) {
            selectedEdgeIds.value.add(edgeId);
          }
        }
      }
      if (!areSetsEqual(prevSelectedNodeIds, selectedNodeIds.value)) {
        const changes = getSelectionChanges(nodeLookup.value, selectedNodeIds.value, true);
        emits.nodesChange(changes);
      }
      if (!areSetsEqual(prevSelectedEdgeIds, selectedEdgeIds.value)) {
        const changes = getSelectionChanges(edgeLookup.value, selectedEdgeIds.value);
        emits.edgesChange(changes);
      }
      userSelectionRect.value = nextUserSelectRect;
      userSelectionActive.value = true;
      nodesSelectionActive.value = false;
    }
    function onPointerUp(event) {
      var _a;
      if (event.button !== 0 || !selectionStarted) {
        return;
      }
      (_a = event.target) == null ? void 0 : _a.releasePointerCapture(event.pointerId);
      if (!userSelectionActive.value && userSelectionRect.value && event.target === container.value) {
        onClick(event);
      }
      userSelectionActive.value = false;
      userSelectionRect.value = null;
      nodesSelectionActive.value = selectedNodeIds.value.size > 0;
      emits.selectionEnd(event);
      if (__props.selectionKeyPressed) {
        selectionInProgress = false;
      }
      selectionStarted = false;
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        ref_key: "container",
        ref: container,
        class: normalizeClass(["vue-flow__pane vue-flow__container", {
          selection: _ctx.isSelecting
        }]),
        onClick: _cache[0] || (_cache[0] = event => hasActiveSelection.value ? void 0 : wrapHandler(onClick, container.value)(event)),
        onContextmenu: _cache[1] || (_cache[1] = $event => wrapHandler(onContextMenu, container.value)($event)),
        onWheelPassive: _cache[2] || (_cache[2] = $event => wrapHandler(onWheel, container.value)($event)),
        onPointerenter: _cache[3] || (_cache[3] = event => hasActiveSelection.value ? void 0 : unref(emits).paneMouseEnter(event)),
        onPointerdown: _cache[4] || (_cache[4] = event => hasActiveSelection.value ? onPointerDown(event) : unref(emits).paneMouseMove(event)),
        onPointermove: _cache[5] || (_cache[5] = event => hasActiveSelection.value ? onPointerMove(event) : unref(emits).paneMouseMove(event)),
        onPointerup: _cache[6] || (_cache[6] = event => hasActiveSelection.value ? onPointerUp(event) : void 0),
        onPointerleave: _cache[7] || (_cache[7] = $event => unref(emits).paneMouseLeave($event))
      }, [renderSlot(_ctx.$slots, "default"), unref(userSelectionActive) && unref(userSelectionRect) ? (openBlock(), createBlock(_sfc_main$c, {
        key: 0,
        "user-selection-rect": unref(userSelectionRect)
      }, null, 8, ["user-selection-rect"])) : createCommentVNode("", true), unref(nodesSelectionActive) && unref(getSelectedNodes).length ? (openBlock(), createBlock(_sfc_main$b, {
        key: 1
      })) : createCommentVNode("", true)], 34);
    };
  }
}));
const __default__$9 = {
  name: "Transform",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$9 = /* @__PURE__ */defineComponent(_extends({}, __default__$9, {
  setup(__props) {
    const _useVueFlow1 = useVueFlow(),
      viewport = _useVueFlow1.viewport,
      fitViewOnInit = _useVueFlow1.fitViewOnInit,
      fitViewOnInitDone = _useVueFlow1.fitViewOnInitDone;
    const isHidden = computed(() => {
      if (fitViewOnInit.value) {
        return !fitViewOnInitDone.value;
      }
      return false;
    });
    const transform = computed(() => "translate(" + viewport.value.x + "px," + viewport.value.y + "px) scale(" + viewport.value.zoom + ")");
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: "vue-flow__transformationpane vue-flow__container",
        style: normalizeStyle({
          transform: transform.value,
          opacity: isHidden.value ? 0 : void 0
        })
      }, [renderSlot(_ctx.$slots, "default")], 4);
    };
  }
}));
const __default__$8 = {
  name: "Viewport",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$8 = /* @__PURE__ */defineComponent(_extends({}, __default__$8, {
  setup(__props) {
    const _useVueFlow10 = useVueFlow(),
      minZoom = _useVueFlow10.minZoom,
      maxZoom = _useVueFlow10.maxZoom,
      defaultViewport = _useVueFlow10.defaultViewport,
      translateExtent = _useVueFlow10.translateExtent,
      zoomActivationKeyCode = _useVueFlow10.zoomActivationKeyCode,
      selectionKeyCode = _useVueFlow10.selectionKeyCode,
      panActivationKeyCode = _useVueFlow10.panActivationKeyCode,
      panOnScroll = _useVueFlow10.panOnScroll,
      panOnScrollMode = _useVueFlow10.panOnScrollMode,
      panOnScrollSpeed = _useVueFlow10.panOnScrollSpeed,
      panOnDrag = _useVueFlow10.panOnDrag,
      zoomOnDoubleClick = _useVueFlow10.zoomOnDoubleClick,
      zoomOnPinch = _useVueFlow10.zoomOnPinch,
      zoomOnScroll = _useVueFlow10.zoomOnScroll,
      preventScrolling = _useVueFlow10.preventScrolling,
      noWheelClassName = _useVueFlow10.noWheelClassName,
      noPanClassName = _useVueFlow10.noPanClassName,
      emits = _useVueFlow10.emits,
      connectionStartHandle = _useVueFlow10.connectionStartHandle,
      userSelectionActive = _useVueFlow10.userSelectionActive,
      paneDragging = _useVueFlow10.paneDragging,
      storeD3Zoom = _useVueFlow10.d3Zoom,
      storeD3Selection = _useVueFlow10.d3Selection,
      storeD3ZoomHandler = _useVueFlow10.d3ZoomHandler,
      viewport = _useVueFlow10.viewport,
      viewportRef = _useVueFlow10.viewportRef,
      paneClickDistance = _useVueFlow10.paneClickDistance;
    useResizeHandler(viewportRef);
    const isZoomingOrPanning = shallowRef(false);
    const isPanScrolling = shallowRef(false);
    let panScrollTimeout = null;
    let zoomedWithRightMouseButton = false;
    let mouseButton = 0;
    let prevTransform = {
      x: 0,
      y: 0,
      zoom: 0
    };
    const panKeyPressed = useKeyPress(panActivationKeyCode);
    const selectionKeyPressed = useKeyPress(selectionKeyCode);
    const zoomKeyPressed = useKeyPress(zoomActivationKeyCode);
    const shouldPanOnDrag = toRef(() => (!selectionKeyPressed.value || selectionKeyPressed.value && selectionKeyCode.value === true) && (panKeyPressed.value || panOnDrag.value));
    const shouldPanOnScroll = toRef(() => panKeyPressed.value || panOnScroll.value);
    const shouldSelectOnDrag = toRef(() => selectionKeyCode.value === true && shouldPanOnDrag.value !== true);
    const isSelecting = toRef(() => selectionKeyPressed.value && selectionKeyCode.value !== true || userSelectionActive.value || shouldSelectOnDrag.value);
    const connectionInProgress = toRef(() => connectionStartHandle.value !== null);
    onMounted(() => {
      var _defaultViewport$valu, _defaultViewport$valu2, _defaultViewport$valu3;
      if (!viewportRef.value) {
        warn("Viewport element is missing");
        return;
      }
      const viewportElement = viewportRef.value;
      const bbox = viewportElement.getBoundingClientRect();
      const d3Zoom = zoom$1().clickDistance(paneClickDistance.value).scaleExtent([minZoom.value, maxZoom.value]).translateExtent(translateExtent.value);
      const d3Selection = select$1(viewportElement).call(d3Zoom);
      const d3ZoomHandler = d3Selection.on("wheel.zoom");
      const updatedTransform = identity$2.translate((_defaultViewport$valu = defaultViewport.value.x) != null ? _defaultViewport$valu : 0, (_defaultViewport$valu2 = defaultViewport.value.y) != null ? _defaultViewport$valu2 : 0).scale(clamp((_defaultViewport$valu3 = defaultViewport.value.zoom) != null ? _defaultViewport$valu3 : 1, minZoom.value, maxZoom.value));
      const extent = [[0, 0], [bbox.width, bbox.height]];
      const constrainedTransform = d3Zoom.constrain()(updatedTransform, extent, translateExtent.value);
      d3Zoom.transform(d3Selection, constrainedTransform);
      d3Zoom.wheelDelta(wheelDelta);
      storeD3Zoom.value = d3Zoom;
      storeD3Selection.value = d3Selection;
      storeD3ZoomHandler.value = d3ZoomHandler;
      viewport.value = {
        x: constrainedTransform.x,
        y: constrainedTransform.y,
        zoom: constrainedTransform.k
      };
      d3Zoom.on("start", event => {
        var _a;
        if (!event.sourceEvent) {
          return null;
        }
        mouseButton = event.sourceEvent.button;
        isZoomingOrPanning.value = true;
        const flowTransform = eventToFlowTransform(event.transform);
        if (((_a = event.sourceEvent) == null ? void 0 : _a.type) === "mousedown") {
          paneDragging.value = true;
        }
        prevTransform = flowTransform;
        emits.viewportChangeStart(flowTransform);
        emits.moveStart({
          event,
          flowTransform
        });
      });
      d3Zoom.on("end", event => {
        if (!event.sourceEvent) {
          return null;
        }
        isZoomingOrPanning.value = false;
        paneDragging.value = false;
        if (isRightClickPan(shouldPanOnDrag.value, mouseButton != null ? mouseButton : 0) && !zoomedWithRightMouseButton) {
          emits.paneContextMenu(event.sourceEvent);
        }
        zoomedWithRightMouseButton = false;
        if (viewChanged(prevTransform, event.transform)) {
          const flowTransform = eventToFlowTransform(event.transform);
          prevTransform = flowTransform;
          emits.viewportChangeEnd(flowTransform);
          emits.moveEnd({
            event,
            flowTransform
          });
        }
      });
      d3Zoom.filter(event => {
        var _a;
        const zoomScroll = zoomKeyPressed.value || zoomOnScroll.value;
        const pinchZoom = zoomOnPinch.value && event.ctrlKey;
        const eventButton = event.button;
        const isWheelEvent = event.type === "wheel";
        if (eventButton === 1 && event.type === "mousedown" && (isWrappedWithClass(event, "vue-flow__node") || isWrappedWithClass(event, "vue-flow__edge"))) {
          return true;
        }
        if (!shouldPanOnDrag.value && !zoomScroll && !shouldPanOnScroll.value && !zoomOnDoubleClick.value && !zoomOnPinch.value) {
          return false;
        }
        if (userSelectionActive.value) {
          return false;
        }
        if (connectionInProgress.value && !isWheelEvent) {
          return false;
        }
        if (!zoomOnDoubleClick.value && event.type === "dblclick") {
          return false;
        }
        if (isWrappedWithClass(event, noWheelClassName.value) && isWheelEvent) {
          return false;
        }
        if (isWrappedWithClass(event, noPanClassName.value) && (!isWheelEvent || shouldPanOnScroll.value && isWheelEvent && !zoomKeyPressed.value)) {
          return false;
        }
        if (!zoomOnPinch.value && event.ctrlKey && isWheelEvent) {
          return false;
        }
        if (!zoomScroll && !shouldPanOnScroll.value && !pinchZoom && isWheelEvent) {
          return false;
        }
        if (!zoomOnPinch && event.type === "touchstart" && ((_a = event.touches) == null ? void 0 : _a.length) > 1) {
          event.preventDefault();
          return false;
        }
        if (!shouldPanOnDrag.value && (event.type === "mousedown" || event.type === "touchstart")) {
          return false;
        }
        if (shouldSelectOnDrag.value && Array.isArray(panOnDrag.value) && panOnDrag.value.includes(0) && eventButton === 0) {
          return false;
        }
        if (Array.isArray(panOnDrag.value) && !panOnDrag.value.includes(eventButton) && (event.type === "mousedown" || event.type === "touchstart")) {
          return false;
        }
        const buttonAllowed = Array.isArray(panOnDrag.value) && panOnDrag.value.includes(eventButton) || selectionKeyCode.value === true && Array.isArray(panOnDrag.value) && !panOnDrag.value.includes(0) || !eventButton || eventButton <= 1;
        return (!event.ctrlKey || panKeyPressed.value || isWheelEvent) && buttonAllowed;
      });
      watch([userSelectionActive, shouldPanOnDrag], () => {
        if (userSelectionActive.value && !isZoomingOrPanning.value) {
          d3Zoom.on("zoom", null);
        } else if (!userSelectionActive.value) {
          d3Zoom.on("zoom", event => {
            viewport.value = {
              x: event.transform.x,
              y: event.transform.y,
              zoom: event.transform.k
            };
            const flowTransform = eventToFlowTransform(event.transform);
            zoomedWithRightMouseButton = isRightClickPan(shouldPanOnDrag.value, mouseButton != null ? mouseButton : 0);
            emits.viewportChange(flowTransform);
            emits.move({
              event,
              flowTransform
            });
          });
        }
      }, {
        immediate: true
      });
      watch([userSelectionActive, shouldPanOnScroll, panOnScrollMode, zoomKeyPressed, zoomOnPinch, preventScrolling, noWheelClassName], () => {
        if (shouldPanOnScroll.value && !zoomKeyPressed.value && !userSelectionActive.value) {
          d3Selection.on("wheel.zoom", event => {
            if (isWrappedWithClass(event, noWheelClassName.value)) {
              return false;
            }
            const zoomScroll = zoomKeyPressed.value || zoomOnScroll.value;
            const pinchZoom = zoomOnPinch.value && event.ctrlKey;
            const scrollEventEnabled = !preventScrolling.value || shouldPanOnScroll.value || zoomScroll || pinchZoom;
            if (!scrollEventEnabled) {
              return false;
            }
            event.preventDefault();
            event.stopImmediatePropagation();
            const currentZoom = d3Selection.property("__zoom").k || 1;
            const _isMacOs = isMacOs();
            if (!panKeyPressed.value && event.ctrlKey && zoomOnPinch.value && _isMacOs) {
              const point = pointer$1(event);
              const pinchDelta = wheelDelta(event);
              const zoom2 = currentZoom * 2 ** pinchDelta;
              d3Zoom.scaleTo(d3Selection, zoom2, point, event);
              return;
            }
            const deltaNormalize = event.deltaMode === 1 ? 20 : 1;
            let deltaX = panOnScrollMode.value === PanOnScrollMode.Vertical ? 0 : event.deltaX * deltaNormalize;
            let deltaY = panOnScrollMode.value === PanOnScrollMode.Horizontal ? 0 : event.deltaY * deltaNormalize;
            if (!_isMacOs && event.shiftKey && panOnScrollMode.value !== PanOnScrollMode.Vertical && !deltaX && deltaY) {
              deltaX = deltaY;
              deltaY = 0;
            }
            d3Zoom.translateBy(d3Selection, -(deltaX / currentZoom) * panOnScrollSpeed.value, -(deltaY / currentZoom) * panOnScrollSpeed.value);
            const nextViewport = eventToFlowTransform(d3Selection.property("__zoom"));
            if (panScrollTimeout) {
              clearTimeout(panScrollTimeout);
            }
            if (!isPanScrolling.value) {
              isPanScrolling.value = true;
              emits.moveStart({
                event,
                flowTransform: nextViewport
              });
              emits.viewportChangeStart(nextViewport);
            } else {
              emits.move({
                event,
                flowTransform: nextViewport
              });
              emits.viewportChange(nextViewport);
              panScrollTimeout = setTimeout(() => {
                emits.moveEnd({
                  event,
                  flowTransform: nextViewport
                });
                emits.viewportChangeEnd(nextViewport);
                isPanScrolling.value = false;
              }, 150);
            }
          }, {
            passive: false
          });
        } else if (typeof d3ZoomHandler !== "undefined") {
          d3Selection.on("wheel.zoom", function (event, d) {
            const invalidEvent = !preventScrolling.value && event.type === "wheel" && !event.ctrlKey;
            const zoomScroll = zoomKeyPressed.value || zoomOnScroll.value;
            const pinchZoom = zoomOnPinch.value && event.ctrlKey;
            const scrollEventsDisabled = !zoomScroll && !panOnScroll.value && !pinchZoom && event.type === "wheel";
            if (scrollEventsDisabled || invalidEvent || isWrappedWithClass(event, noWheelClassName.value)) {
              return null;
            }
            event.preventDefault();
            d3ZoomHandler.call(this, event, d);
          }, {
            passive: false
          });
        }
      }, {
        immediate: true
      });
    });
    function isRightClickPan(pan, usedButton) {
      return usedButton === 2 && Array.isArray(pan) && pan.includes(2);
    }
    function viewChanged(prevViewport, eventTransform) {
      return prevViewport.x !== eventTransform.x && !Number.isNaN(eventTransform.x) || prevViewport.y !== eventTransform.y && !Number.isNaN(eventTransform.y) || prevViewport.zoom !== eventTransform.k && !Number.isNaN(eventTransform.k);
    }
    function eventToFlowTransform(eventTransform) {
      return {
        x: eventTransform.x,
        y: eventTransform.y,
        zoom: eventTransform.k
      };
    }
    function isWrappedWithClass(event, className) {
      return event.target.closest("." + className);
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        ref_key: "viewportRef",
        ref: viewportRef,
        class: "vue-flow__viewport vue-flow__container"
      }, [createVNode(_sfc_main$a, {
        "is-selecting": isSelecting.value,
        "selection-key-pressed": unref(selectionKeyPressed),
        class: normalizeClass({
          connecting: connectionInProgress.value,
          dragging: unref(paneDragging),
          draggable: unref(panOnDrag) === true || Array.isArray(unref(panOnDrag)) && unref(panOnDrag).includes(0)
        })
      }, {
        default: withCtx(() => [createVNode(_sfc_main$9, null, {
          default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
          _: 3
        })]),
        _: 3
      }, 8, ["is-selecting", "selection-key-pressed", "class"])], 512);
    };
  }
}));
const _hoisted_1$5$1 = ["id"];
const _hoisted_2$8 = ["id"];
const _hoisted_3$8 = ["id"];
const __default__$7 = {
  name: "A11yDescriptions",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$7 = /* @__PURE__ */defineComponent(_extends({}, __default__$7, {
  setup(__props) {
    const _useVueFlow11 = useVueFlow(),
      id2 = _useVueFlow11.id,
      disableKeyboardA11y = _useVueFlow11.disableKeyboardA11y,
      ariaLiveMessage = _useVueFlow11.ariaLiveMessage;
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock(Fragment, null, [createBaseVNode("div", {
        id: unref(ARIA_NODE_DESC_KEY) + "-" + unref(id2),
        style: {
          "display": "none"
        }
      }, " Press enter or space to select a node. " + toDisplayString(!unref(disableKeyboardA11y) ? "You can then use the arrow keys to move the node around." : "") + " You can then use the arrow keys to move the node around, press delete to remove it and press escape to cancel. ", 9, _hoisted_1$5$1), createBaseVNode("div", {
        id: unref(ARIA_EDGE_DESC_KEY) + "-" + unref(id2),
        style: {
          "display": "none"
        }
      }, " Press enter or space to select an edge. You can then press delete to remove it or press escape to cancel. ", 8, _hoisted_2$8), !unref(disableKeyboardA11y) ? (openBlock(), createElementBlock("div", {
        key: 0,
        id: unref(ARIA_LIVE_MESSAGE) + "-" + unref(id2),
        "aria-live": "assertive",
        "aria-atomic": "true",
        style: {
          "position": "absolute",
          "width": "1px",
          "height": "1px",
          "margin": "-1px",
          "border": "0",
          "padding": "0",
          "overflow": "hidden",
          "clip": "rect(0px, 0px, 0px, 0px)",
          "clip-path": "inset(100%)"
        }
      }, toDisplayString(unref(ariaLiveMessage)), 9, _hoisted_3$8)) : createCommentVNode("", true)], 64);
    };
  }
}));
function useOnInitHandler() {
  const vfInstance = useVueFlow();
  watch(() => vfInstance.viewportHelper.value.viewportInitialized, isInitialized => {
    if (isInitialized) {
      setTimeout(() => {
        vfInstance.emits.init(vfInstance);
        vfInstance.emits.paneReady(vfInstance);
      }, 1);
    }
  });
}
function shiftX(x, shift, position) {
  if (position === Position.Left) {
    return x - shift;
  }
  if (position === Position.Right) {
    return x + shift;
  }
  return x;
}
function shiftY(y, shift, position) {
  if (position === Position.Top) {
    return y - shift;
  }
  if (position === Position.Bottom) {
    return y + shift;
  }
  return y;
}
const EdgeAnchor = function EdgeAnchor(_ref85) {
  let _ref85$radius = _ref85.radius,
    radius = _ref85$radius === void 0 ? 10 : _ref85$radius,
    _ref85$centerX = _ref85.centerX,
    centerX = _ref85$centerX === void 0 ? 0 : _ref85$centerX,
    _ref85$centerY = _ref85.centerY,
    centerY = _ref85$centerY === void 0 ? 0 : _ref85$centerY,
    _ref85$position = _ref85.position,
    position = _ref85$position === void 0 ? Position.Top : _ref85$position,
    type = _ref85.type;
  return h("circle", {
    class: "vue-flow__edgeupdater vue-flow__edgeupdater-" + type,
    cx: shiftX(centerX, radius, position),
    cy: shiftY(centerY, radius, position),
    r: radius,
    stroke: "transparent",
    fill: "transparent"
  });
};
EdgeAnchor.props = ["radius", "centerX", "centerY", "position", "type"];
EdgeAnchor.compatConfig = {
  MODE: 3
};
const EdgeAnchor$1 = EdgeAnchor;
const EdgeWrapper = defineComponent({
  name: "Edge",
  compatConfig: {
    MODE: 3
  },
  props: ["id"],
  setup(props) {
    const _useVueFlow12 = useVueFlow(),
      vueFlowId = _useVueFlow12.id,
      addSelectedEdges = _useVueFlow12.addSelectedEdges,
      connectionMode = _useVueFlow12.connectionMode,
      edgeUpdaterRadius = _useVueFlow12.edgeUpdaterRadius,
      emits = _useVueFlow12.emits,
      nodesSelectionActive = _useVueFlow12.nodesSelectionActive,
      noPanClassName = _useVueFlow12.noPanClassName,
      getEdgeTypes = _useVueFlow12.getEdgeTypes,
      removeSelectedEdges = _useVueFlow12.removeSelectedEdges,
      findEdge = _useVueFlow12.findEdge,
      findNode = _useVueFlow12.findNode,
      isValidConnection = _useVueFlow12.isValidConnection,
      multiSelectionActive = _useVueFlow12.multiSelectionActive,
      disableKeyboardA11y = _useVueFlow12.disableKeyboardA11y,
      elementsSelectable = _useVueFlow12.elementsSelectable,
      edgesUpdatable = _useVueFlow12.edgesUpdatable,
      edgesFocusable = _useVueFlow12.edgesFocusable,
      hooks = _useVueFlow12.hooks;
    const edge = computed(() => findEdge(props.id));
    const _useEdgeHooks = useEdgeHooks(edge.value, emits),
      emit = _useEdgeHooks.emit,
      on = _useEdgeHooks.on;
    const slots = inject(Slots$1);
    const instance = getCurrentInstance();
    const mouseOver = ref(false);
    const updating = ref(false);
    const nodeId = ref("");
    const handleId = ref(null);
    const edgeUpdaterType = ref("source");
    const edgeEl = ref(null);
    const isSelectable = toRef(() => typeof edge.value.selectable === "undefined" ? elementsSelectable.value : edge.value.selectable);
    const isUpdatable = toRef(() => typeof edge.value.updatable === "undefined" ? edgesUpdatable.value : edge.value.updatable);
    const isFocusable = toRef(() => typeof edge.value.focusable === "undefined" ? edgesFocusable.value : edge.value.focusable);
    provide(EdgeId, props.id);
    provide(EdgeRef, edgeEl);
    const edgeClass = computed(() => edge.value.class instanceof Function ? edge.value.class(edge.value) : edge.value.class);
    const edgeStyle = computed(() => edge.value.style instanceof Function ? edge.value.style(edge.value) : edge.value.style);
    const edgeCmp = computed(() => {
      var _edge$value$template;
      const name = edge.value.type || "default";
      const slot = slots == null ? void 0 : slots["edge-" + name];
      if (slot) {
        return slot;
      }
      let edgeType = (_edge$value$template = edge.value.template) != null ? _edge$value$template : getEdgeTypes.value[name];
      if (typeof edgeType === "string") {
        if (instance) {
          const components = Object.keys(instance.appContext.components);
          if (components && components.includes(name)) {
            edgeType = resolveComponent(name, false);
          }
        }
      }
      if (edgeType && typeof edgeType !== "string") {
        return edgeType;
      }
      emits.error(new VueFlowError(ErrorCode.EDGE_TYPE_MISSING, edgeType));
      return false;
    });
    const _useHandle2 = useHandle({
        nodeId,
        handleId,
        type: edgeUpdaterType,
        isValidConnection,
        edgeUpdaterType,
        onEdgeUpdate,
        onEdgeUpdateEnd
      }),
      handlePointerDown = _useHandle2.handlePointerDown;
    return () => {
      var _edge$value$ariaLabel;
      const sourceNode = findNode(edge.value.source);
      const targetNode = findNode(edge.value.target);
      const pathOptions = "pathOptions" in edge.value ? edge.value.pathOptions : {};
      if (!sourceNode && !targetNode) {
        emits.error(new VueFlowError(ErrorCode.EDGE_SOURCE_TARGET_MISSING, edge.value.id, edge.value.source, edge.value.target));
        return null;
      }
      if (!sourceNode) {
        emits.error(new VueFlowError(ErrorCode.EDGE_SOURCE_MISSING, edge.value.id, edge.value.source));
        return null;
      }
      if (!targetNode) {
        emits.error(new VueFlowError(ErrorCode.EDGE_TARGET_MISSING, edge.value.id, edge.value.target));
        return null;
      }
      if (!edge.value || edge.value.hidden || sourceNode.hidden || targetNode.hidden) {
        return null;
      }
      let sourceNodeHandles;
      if (connectionMode.value === ConnectionMode.Strict) {
        sourceNodeHandles = sourceNode.handleBounds.source;
      } else {
        sourceNodeHandles = [...(sourceNode.handleBounds.source || []), ...(sourceNode.handleBounds.target || [])];
      }
      const sourceHandle = getEdgeHandle(sourceNodeHandles, edge.value.sourceHandle);
      let targetNodeHandles;
      if (connectionMode.value === ConnectionMode.Strict) {
        targetNodeHandles = targetNode.handleBounds.target;
      } else {
        targetNodeHandles = [...(targetNode.handleBounds.target || []), ...(targetNode.handleBounds.source || [])];
      }
      const targetHandle = getEdgeHandle(targetNodeHandles, edge.value.targetHandle);
      const sourcePosition = (sourceHandle == null ? void 0 : sourceHandle.position) || Position.Bottom;
      const targetPosition = (targetHandle == null ? void 0 : targetHandle.position) || Position.Top;
      const _getHandlePosition2 = getHandlePosition(sourceNode, sourceHandle, sourcePosition),
        sourceX = _getHandlePosition2.x,
        sourceY = _getHandlePosition2.y;
      const _getHandlePosition3 = getHandlePosition(targetNode, targetHandle, targetPosition),
        targetX = _getHandlePosition3.x,
        targetY = _getHandlePosition3.y;
      edge.value.sourceX = sourceX;
      edge.value.sourceY = sourceY;
      edge.value.targetX = targetX;
      edge.value.targetY = targetY;
      return h("g", _extends({
        "ref": edgeEl,
        "key": props.id,
        "data-id": props.id,
        "class": ["vue-flow__edge", "vue-flow__edge-" + (edgeCmp.value === false ? "default" : edge.value.type || "default"), noPanClassName.value, edgeClass.value, {
          updating: mouseOver.value,
          selected: edge.value.selected,
          animated: edge.value.animated,
          inactive: !isSelectable.value && !hooks.value.edgeClick.hasListeners()
        }],
        "tabIndex": isFocusable.value ? 0 : void 0,
        "aria-label": edge.value.ariaLabel === null ? void 0 : (_edge$value$ariaLabel = edge.value.ariaLabel) != null ? _edge$value$ariaLabel : "Edge from " + edge.value.source + " to " + edge.value.target,
        "aria-describedby": isFocusable.value ? ARIA_EDGE_DESC_KEY + "-" + vueFlowId : void 0,
        "aria-roledescription": "edge",
        "role": isFocusable.value ? "group" : "img"
      }, edge.value.domAttributes, {
        "onClick": onEdgeClick,
        "onContextmenu": onEdgeContextMenu,
        "onDblclick": onDoubleClick,
        "onMouseenter": onEdgeMouseEnter,
        "onMousemove": onEdgeMouseMove,
        "onMouseleave": onEdgeMouseLeave,
        "onKeyDown": isFocusable.value ? onKeyDown : void 0
      }), [updating.value ? null : h(edgeCmp.value === false ? getEdgeTypes.value.default : edgeCmp.value, _extends({
        id: props.id,
        sourceNode,
        targetNode,
        source: edge.value.source,
        target: edge.value.target,
        type: edge.value.type,
        updatable: isUpdatable.value,
        selected: edge.value.selected,
        animated: edge.value.animated,
        label: edge.value.label,
        labelStyle: edge.value.labelStyle,
        labelShowBg: edge.value.labelShowBg,
        labelBgStyle: edge.value.labelBgStyle,
        labelBgPadding: edge.value.labelBgPadding,
        labelBgBorderRadius: edge.value.labelBgBorderRadius,
        data: edge.value.data,
        events: _extends({}, edge.value.events, on),
        style: edgeStyle.value,
        markerStart: "url('#" + getMarkerId(edge.value.markerStart, vueFlowId) + "')",
        markerEnd: "url('#" + getMarkerId(edge.value.markerEnd, vueFlowId) + "')",
        sourcePosition,
        targetPosition,
        sourceX,
        sourceY,
        targetX,
        targetY,
        sourceHandleId: edge.value.sourceHandle,
        targetHandleId: edge.value.targetHandle,
        interactionWidth: edge.value.interactionWidth
      }, pathOptions)), [isUpdatable.value === "source" || isUpdatable.value === true ? [h("g", {
        onMousedown: onEdgeUpdaterSourceMouseDown,
        onMouseenter: onEdgeUpdaterMouseEnter,
        onMouseout: onEdgeUpdaterMouseOut
      }, h(EdgeAnchor$1, {
        "position": sourcePosition,
        "centerX": sourceX,
        "centerY": sourceY,
        "radius": edgeUpdaterRadius.value,
        "type": "source",
        "data-type": "source"
      }))] : null, isUpdatable.value === "target" || isUpdatable.value === true ? [h("g", {
        onMousedown: onEdgeUpdaterTargetMouseDown,
        onMouseenter: onEdgeUpdaterMouseEnter,
        onMouseout: onEdgeUpdaterMouseOut
      }, h(EdgeAnchor$1, {
        "position": targetPosition,
        "centerX": targetX,
        "centerY": targetY,
        "radius": edgeUpdaterRadius.value,
        "type": "target",
        "data-type": "target"
      }))] : null]]);
    };
    function onEdgeUpdaterMouseEnter() {
      mouseOver.value = true;
    }
    function onEdgeUpdaterMouseOut() {
      mouseOver.value = false;
    }
    function onEdgeUpdate(event, connection) {
      emit.update({
        event,
        edge: edge.value,
        connection
      });
    }
    function onEdgeUpdateEnd(event) {
      emit.updateEnd({
        event,
        edge: edge.value
      });
      updating.value = false;
    }
    function handleEdgeUpdater(event, isSourceHandle) {
      var _ref86;
      if (event.button !== 0) {
        return;
      }
      updating.value = true;
      nodeId.value = isSourceHandle ? edge.value.target : edge.value.source;
      handleId.value = (_ref86 = isSourceHandle ? edge.value.targetHandle : edge.value.sourceHandle) != null ? _ref86 : null;
      edgeUpdaterType.value = isSourceHandle ? "target" : "source";
      emit.updateStart({
        event,
        edge: edge.value
      });
      handlePointerDown(event);
    }
    function onEdgeClick(event) {
      var _a;
      const data = {
        event,
        edge: edge.value
      };
      if (isSelectable.value) {
        nodesSelectionActive.value = false;
        if (edge.value.selected && multiSelectionActive.value) {
          removeSelectedEdges([edge.value]);
          (_a = edgeEl.value) == null ? void 0 : _a.blur();
        } else {
          addSelectedEdges([edge.value]);
        }
      }
      emit.click(data);
    }
    function onEdgeContextMenu(event) {
      emit.contextMenu({
        event,
        edge: edge.value
      });
    }
    function onDoubleClick(event) {
      emit.doubleClick({
        event,
        edge: edge.value
      });
    }
    function onEdgeMouseEnter(event) {
      emit.mouseEnter({
        event,
        edge: edge.value
      });
    }
    function onEdgeMouseMove(event) {
      emit.mouseMove({
        event,
        edge: edge.value
      });
    }
    function onEdgeMouseLeave(event) {
      emit.mouseLeave({
        event,
        edge: edge.value
      });
    }
    function onEdgeUpdaterSourceMouseDown(event) {
      handleEdgeUpdater(event, true);
    }
    function onEdgeUpdaterTargetMouseDown(event) {
      handleEdgeUpdater(event, false);
    }
    function onKeyDown(event) {
      var _a;
      if (!disableKeyboardA11y.value && elementSelectionKeys.includes(event.key) && isSelectable.value) {
        const unselect = event.key === "Escape";
        if (unselect) {
          (_a = edgeEl.value) == null ? void 0 : _a.blur();
          removeSelectedEdges([findEdge(props.id)]);
        } else {
          addSelectedEdges([findEdge(props.id)]);
        }
      }
    }
  }
});
const EdgeWrapper$1 = EdgeWrapper;
const ConnectionLine = defineComponent({
  name: "ConnectionLine",
  compatConfig: {
    MODE: 3
  },
  setup() {
    var _a;
    const _useVueFlow13 = useVueFlow(),
      id2 = _useVueFlow13.id,
      connectionMode = _useVueFlow13.connectionMode,
      connectionStartHandle = _useVueFlow13.connectionStartHandle,
      connectionEndHandle = _useVueFlow13.connectionEndHandle,
      connectionPosition = _useVueFlow13.connectionPosition,
      connectionLineType = _useVueFlow13.connectionLineType,
      connectionLineStyle = _useVueFlow13.connectionLineStyle,
      connectionLineOptions = _useVueFlow13.connectionLineOptions,
      connectionStatus = _useVueFlow13.connectionStatus,
      viewport = _useVueFlow13.viewport,
      findNode = _useVueFlow13.findNode;
    const connectionLineComponent = (_a = inject(Slots$1)) == null ? void 0 : _a["connection-line"];
    const fromNode = computed(() => {
      var _a2;
      return findNode((_a2 = connectionStartHandle.value) == null ? void 0 : _a2.nodeId);
    });
    const toNode = computed(() => {
      var _findNode;
      var _a2;
      return (_findNode = findNode((_a2 = connectionEndHandle.value) == null ? void 0 : _a2.nodeId)) != null ? _findNode : null;
    });
    const toXY = computed(() => {
      return {
        x: (connectionPosition.value.x - viewport.value.x) / viewport.value.zoom,
        y: (connectionPosition.value.y - viewport.value.y) / viewport.value.zoom
      };
    });
    const markerStart = computed(() => connectionLineOptions.value.markerStart ? "url(#" + getMarkerId(connectionLineOptions.value.markerStart, id2) + ")" : "");
    const markerEnd = computed(() => connectionLineOptions.value.markerEnd ? "url(#" + getMarkerId(connectionLineOptions.value.markerEnd, id2) + ")" : "");
    return () => {
      var _ref87, _ref89, _ref90, _ref91, _ref92, _connectionLineType$v;
      var _a2, _b, _c;
      if (!fromNode.value || !connectionStartHandle.value) {
        return null;
      }
      const startHandleId = connectionStartHandle.value.id;
      const handleType = connectionStartHandle.value.type;
      const fromHandleBounds = fromNode.value.handleBounds;
      let handleBounds = (_ref87 = fromHandleBounds == null ? void 0 : fromHandleBounds[handleType]) != null ? _ref87 : [];
      if (connectionMode.value === ConnectionMode.Loose) {
        var _ref88;
        const oppositeBounds = (_ref88 = fromHandleBounds == null ? void 0 : fromHandleBounds[handleType === "source" ? "target" : "source"]) != null ? _ref88 : [];
        handleBounds = [...handleBounds, ...oppositeBounds];
      }
      if (!handleBounds) {
        return null;
      }
      const fromHandle = (_ref89 = startHandleId ? handleBounds.find(d => d.id === startHandleId) : handleBounds[0]) != null ? _ref89 : null;
      const fromPosition = (_ref90 = fromHandle == null ? void 0 : fromHandle.position) != null ? _ref90 : Position.Top;
      const _getHandlePosition4 = getHandlePosition(fromNode.value, fromHandle, fromPosition),
        fromX = _getHandlePosition4.x,
        fromY = _getHandlePosition4.y;
      let toHandle = null;
      if (toNode.value) {
        if (connectionMode.value === ConnectionMode.Strict) {
          toHandle = ((_a2 = toNode.value.handleBounds[handleType === "source" ? "target" : "source"]) == null ? void 0 : _a2.find(d => {
            var _a3;
            return d.id === ((_a3 = connectionEndHandle.value) == null ? void 0 : _a3.id);
          })) || null;
        } else {
          var _toNode$value$handleB, _toNode$value$handleB2;
          toHandle = ((_b = [...((_toNode$value$handleB = toNode.value.handleBounds.source) != null ? _toNode$value$handleB : []), ...((_toNode$value$handleB2 = toNode.value.handleBounds.target) != null ? _toNode$value$handleB2 : [])]) == null ? void 0 : _b.find(d => {
            var _a3;
            return d.id === ((_a3 = connectionEndHandle.value) == null ? void 0 : _a3.id);
          })) || null;
        }
      }
      const toPosition = (_ref91 = (_c = connectionEndHandle.value) == null ? void 0 : _c.position) != null ? _ref91 : fromPosition ? oppositePosition[fromPosition] : null;
      if (!fromPosition || !toPosition) {
        return null;
      }
      const type = (_ref92 = (_connectionLineType$v = connectionLineType.value) != null ? _connectionLineType$v : connectionLineOptions.value.type) != null ? _ref92 : ConnectionLineType.Bezier;
      let dAttr = "";
      const pathParams = {
        sourceX: fromX,
        sourceY: fromY,
        sourcePosition: fromPosition,
        targetX: toXY.value.x,
        targetY: toXY.value.y,
        targetPosition: toPosition
      };
      if (type === ConnectionLineType.Bezier) {
        var _getBezierPath2 = getBezierPath(pathParams);
        dAttr = _getBezierPath2[0];
      } else if (type === ConnectionLineType.Step) {
        var _getSmoothStepPath2 = getSmoothStepPath(_extends({}, pathParams, {
          borderRadius: 0
        }));
        dAttr = _getSmoothStepPath2[0];
      } else if (type === ConnectionLineType.SmoothStep) {
        var _getSmoothStepPath3 = getSmoothStepPath(pathParams);
        dAttr = _getSmoothStepPath3[0];
      } else if (type === ConnectionLineType.SimpleBezier) {
        var _getSimpleBezierPath2 = getSimpleBezierPath(pathParams);
        dAttr = _getSimpleBezierPath2[0];
      } else {
        dAttr = "M" + fromX + "," + fromY + " " + toXY.value.x + "," + toXY.value.y;
      }
      return h("svg", {
        class: "vue-flow__edges vue-flow__connectionline vue-flow__container"
      }, h("g", {
        class: "vue-flow__connection"
      }, connectionLineComponent ? h(connectionLineComponent, {
        sourceX: fromX,
        sourceY: fromY,
        sourcePosition: fromPosition,
        targetX: toXY.value.x,
        targetY: toXY.value.y,
        targetPosition: toPosition,
        sourceNode: fromNode.value,
        sourceHandle: fromHandle,
        targetNode: toNode.value,
        targetHandle: toHandle,
        markerEnd: markerEnd.value,
        markerStart: markerStart.value,
        connectionStatus: connectionStatus.value
      }) : h("path", {
        "d": dAttr,
        "class": [connectionLineOptions.value.class, connectionStatus.value, "vue-flow__connection-path"],
        "style": _extends({}, connectionLineStyle.value, connectionLineOptions.value.style),
        "marker-end": markerEnd.value,
        "marker-start": markerStart.value
      })));
    };
  }
});
const ConnectionLine$1 = ConnectionLine;
const _hoisted_1$4$1 = ["id", "markerWidth", "markerHeight", "markerUnits", "orient"];
const __default__$6 = {
  name: "MarkerType",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$6 = /* @__PURE__ */defineComponent(_extends({}, __default__$6, {
  props: {
    id: {},
    type: {},
    color: {
      default: "none"
    },
    width: {
      default: 12.5
    },
    height: {
      default: 12.5
    },
    markerUnits: {
      default: "strokeWidth"
    },
    orient: {
      default: "auto-start-reverse"
    },
    strokeWidth: {
      default: 1
    }
  },
  setup(__props) {
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("marker", {
        id: _ctx.id,
        class: "vue-flow__arrowhead",
        viewBox: "-10 -10 20 20",
        refX: "0",
        refY: "0",
        markerWidth: "" + _ctx.width,
        markerHeight: "" + _ctx.height,
        markerUnits: _ctx.markerUnits,
        orient: _ctx.orient
      }, [_ctx.type === unref(MarkerType).ArrowClosed ? (openBlock(), createElementBlock("polyline", {
        key: 0,
        style: normalizeStyle({
          stroke: _ctx.color,
          fill: _ctx.color,
          strokeWidth: _ctx.strokeWidth
        }),
        "stroke-linecap": "round",
        "stroke-linejoin": "round",
        points: "-5,-4 0,0 -5,4 -5,-4"
      }, null, 4)) : createCommentVNode("", true), _ctx.type === unref(MarkerType).Arrow ? (openBlock(), createElementBlock("polyline", {
        key: 1,
        style: normalizeStyle({
          stroke: _ctx.color,
          strokeWidth: _ctx.strokeWidth
        }),
        "stroke-linecap": "round",
        "stroke-linejoin": "round",
        fill: "none",
        points: "-5,-4 0,0 -5,4"
      }, null, 4)) : createCommentVNode("", true)], 8, _hoisted_1$4$1);
    };
  }
}));
const _hoisted_1$3$1 = {
  class: "vue-flow__marker vue-flow__container",
  "aria-hidden": "true"
};
const __default__$5 = {
  name: "MarkerDefinitions",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$5 = /* @__PURE__ */defineComponent(_extends({}, __default__$5, {
  setup(__props) {
    const _useVueFlow14 = useVueFlow(),
      vueFlowId = _useVueFlow14.id,
      edges = _useVueFlow14.edges,
      connectionLineOptions = _useVueFlow14.connectionLineOptions,
      defaultColor = _useVueFlow14.defaultMarkerColor;
    const markers = computed(() => {
      const ids = /* @__PURE__ */new Set();
      const markers2 = [];
      const createMarkers = marker => {
        if (marker) {
          const markerId = getMarkerId(marker, vueFlowId);
          if (!ids.has(markerId)) {
            if (typeof marker === "object") {
              markers2.push(_extends({}, marker, {
                id: markerId,
                color: marker.color || defaultColor.value
              }));
            } else {
              markers2.push({
                id: markerId,
                color: defaultColor.value,
                type: marker
              });
            }
            ids.add(markerId);
          }
        }
      };
      for (const marker of [connectionLineOptions.value.markerEnd, connectionLineOptions.value.markerStart]) {
        createMarkers(marker);
      }
      for (const edge of edges.value) {
        for (const marker of [edge.markerStart, edge.markerEnd]) {
          createMarkers(marker);
        }
      }
      return markers2.sort((a, b) => a.id.localeCompare(b.id));
    });
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("svg", _hoisted_1$3$1, [createBaseVNode("defs", null, [(openBlock(true), createElementBlock(Fragment, null, renderList(markers.value, marker => {
        return openBlock(), createBlock(_sfc_main$6, {
          id: marker.id,
          key: marker.id,
          type: marker.type,
          color: marker.color,
          width: marker.width,
          height: marker.height,
          markerUnits: marker.markerUnits,
          "stroke-width": marker.strokeWidth,
          orient: marker.orient
        }, null, 8, ["id", "type", "color", "width", "height", "markerUnits", "stroke-width", "orient"]);
      }), 128))])]);
    };
  }
}));
const __default__$4 = {
  name: "Edges",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$4 = /* @__PURE__ */defineComponent(_extends({}, __default__$4, {
  setup(__props) {
    const _useVueFlow15 = useVueFlow(),
      findNode = _useVueFlow15.findNode,
      getEdges = _useVueFlow15.getEdges,
      elevateEdgesOnSelect = _useVueFlow15.elevateEdgesOnSelect;
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock(Fragment, null, [createVNode(_sfc_main$5), (openBlock(true), createElementBlock(Fragment, null, renderList(unref(getEdges), edge => {
        return openBlock(), createElementBlock("svg", {
          key: edge.id,
          class: "vue-flow__edges vue-flow__container",
          style: normalizeStyle({
            zIndex: unref(getEdgeZIndex)(edge, unref(findNode), unref(elevateEdgesOnSelect))
          })
        }, [createVNode(unref(EdgeWrapper$1), {
          id: edge.id
        }, null, 8, ["id"])], 4);
      }), 128)), createVNode(unref(ConnectionLine$1))], 64);
    };
  }
}));
const NodeWrapper = defineComponent({
  name: "Node",
  compatConfig: {
    MODE: 3
  },
  props: ["id", "resizeObserver"],
  setup(props) {
    const _useVueFlow16 = useVueFlow(),
      vueFlowId = _useVueFlow16.id,
      noPanClassName = _useVueFlow16.noPanClassName,
      selectNodesOnDrag = _useVueFlow16.selectNodesOnDrag,
      nodesSelectionActive = _useVueFlow16.nodesSelectionActive,
      multiSelectionActive = _useVueFlow16.multiSelectionActive,
      emits = _useVueFlow16.emits,
      removeSelectedNodes = _useVueFlow16.removeSelectedNodes,
      addSelectedNodes = _useVueFlow16.addSelectedNodes,
      updateNodeDimensions = _useVueFlow16.updateNodeDimensions,
      onUpdateNodeInternals = _useVueFlow16.onUpdateNodeInternals,
      getNodeTypes = _useVueFlow16.getNodeTypes,
      nodeExtent = _useVueFlow16.nodeExtent,
      elevateNodesOnSelect = _useVueFlow16.elevateNodesOnSelect,
      disableKeyboardA11y = _useVueFlow16.disableKeyboardA11y,
      ariaLiveMessage = _useVueFlow16.ariaLiveMessage,
      snapToGrid = _useVueFlow16.snapToGrid,
      snapGrid = _useVueFlow16.snapGrid,
      nodeDragThreshold = _useVueFlow16.nodeDragThreshold,
      nodesDraggable = _useVueFlow16.nodesDraggable,
      elementsSelectable = _useVueFlow16.elementsSelectable,
      nodesConnectable = _useVueFlow16.nodesConnectable,
      nodesFocusable = _useVueFlow16.nodesFocusable,
      hooks = _useVueFlow16.hooks;
    const nodeElement = ref(null);
    provide(NodeRef, nodeElement);
    provide(NodeId, props.id);
    const slots = inject(Slots$1);
    const instance = getCurrentInstance();
    const updateNodePositions = useUpdateNodePositions();
    const _useNode2 = useNode(props.id),
      node = _useNode2.node,
      parentNode = _useNode2.parentNode;
    const _useNodeHooks = useNodeHooks(node, emits),
      emit = _useNodeHooks.emit,
      on = _useNodeHooks.on;
    const isDraggable = toRef(() => typeof node.draggable === "undefined" ? nodesDraggable.value : node.draggable);
    const isSelectable = toRef(() => typeof node.selectable === "undefined" ? elementsSelectable.value : node.selectable);
    const isConnectable = toRef(() => typeof node.connectable === "undefined" ? nodesConnectable.value : node.connectable);
    const isFocusable = toRef(() => typeof node.focusable === "undefined" ? nodesFocusable.value : node.focusable);
    const hasPointerEvents = computed(() => isSelectable.value || isDraggable.value || hooks.value.nodeClick.hasListeners() || hooks.value.nodeDoubleClick.hasListeners() || hooks.value.nodeMouseEnter.hasListeners() || hooks.value.nodeMouseMove.hasListeners() || hooks.value.nodeMouseLeave.hasListeners());
    const isInit = toRef(() => !!node.dimensions.width && !!node.dimensions.height);
    const nodeCmp = computed(() => {
      const name = node.type || "default";
      const slot = slots == null ? void 0 : slots["node-" + name];
      if (slot) {
        return slot;
      }
      let nodeType = node.template || getNodeTypes.value[name];
      if (typeof nodeType === "string") {
        if (instance) {
          const components = Object.keys(instance.appContext.components);
          if (components && components.includes(name)) {
            nodeType = resolveComponent(name, false);
          }
        }
      }
      if (nodeType && typeof nodeType !== "string") {
        return nodeType;
      }
      emits.error(new VueFlowError(ErrorCode.NODE_TYPE_MISSING, nodeType));
      return false;
    });
    const dragging = useDrag({
      id: props.id,
      el: nodeElement,
      disabled: () => !isDraggable.value,
      selectable: isSelectable,
      dragHandle: () => node.dragHandle,
      onStart(event) {
        emit.dragStart(event);
      },
      onDrag(event) {
        emit.drag(event);
      },
      onStop(event) {
        emit.dragStop(event);
      },
      onClick(event) {
        onSelectNode(event);
      }
    });
    const getClass = computed(() => node.class instanceof Function ? node.class(node) : node.class);
    const getStyle = computed(() => {
      const styles = (node.style instanceof Function ? node.style(node) : node.style) || {};
      const width = node.width instanceof Function ? node.width(node) : node.width;
      const height = node.height instanceof Function ? node.height(node) : node.height;
      if (!styles.width && width) {
        styles.width = typeof width === "string" ? width : width + "px";
      }
      if (!styles.height && height) {
        styles.height = typeof height === "string" ? height : height + "px";
      }
      return styles;
    });
    const zIndex = toRef(() => {
      var _ref93, _node$zIndex;
      return Number((_ref93 = (_node$zIndex = node.zIndex) != null ? _node$zIndex : getStyle.value.zIndex) != null ? _ref93 : 0);
    });
    onUpdateNodeInternals(updateIds => {
      if (updateIds.includes(props.id) || !updateIds.length) {
        updateInternals();
      }
    });
    onMounted(() => {
      watch(() => node.hidden, function (isHidden, _, onCleanup) {
        if (isHidden === void 0) {
          isHidden = false;
        }
        if (!isHidden && nodeElement.value) {
          props.resizeObserver.observe(nodeElement.value);
          onCleanup(() => {
            if (nodeElement.value) {
              props.resizeObserver.unobserve(nodeElement.value);
            }
          });
        }
      }, {
        immediate: true,
        flush: "post"
      });
    });
    watch([() => node.type, () => node.sourcePosition, () => node.targetPosition], () => {
      nextTick(() => {
        updateNodeDimensions([{
          id: props.id,
          nodeElement: nodeElement.value,
          forceUpdate: true
        }]);
      });
    });
    watch([() => node.position.x, () => node.position.y, () => {
      var _a;
      return (_a = parentNode.value) == null ? void 0 : _a.computedPosition.x;
    }, () => {
      var _a;
      return (_a = parentNode.value) == null ? void 0 : _a.computedPosition.y;
    }, () => {
      var _a;
      return (_a = parentNode.value) == null ? void 0 : _a.computedPosition.z;
    }, zIndex, () => node.selected, () => node.dimensions.height, () => node.dimensions.width, () => {
      var _a;
      return (_a = parentNode.value) == null ? void 0 : _a.dimensions.height;
    }, () => {
      var _a;
      return (_a = parentNode.value) == null ? void 0 : _a.dimensions.width;
    }], _ref94 => {
      let newX = _ref94[0],
        newY = _ref94[1],
        parentX = _ref94[2],
        parentY = _ref94[3],
        parentZ = _ref94[4],
        nodeZIndex = _ref94[5];
      const xyzPos = {
        x: newX,
        y: newY,
        z: nodeZIndex + (elevateNodesOnSelect.value ? node.selected ? 1e3 : 0 : 0)
      };
      if (typeof parentX !== "undefined" && typeof parentY !== "undefined") {
        node.computedPosition = getXYZPos({
          x: parentX,
          y: parentY,
          z: parentZ
        }, xyzPos);
      } else {
        node.computedPosition = xyzPos;
      }
    }, {
      flush: "post",
      immediate: true
    });
    watch([() => node.extent, nodeExtent], (_ref95, _ref96) => {
      let nodeExtent2 = _ref95[0],
        globalExtent = _ref95[1];
      let oldNodeExtent = _ref96[0],
        oldGlobalExtent = _ref96[1];
      if (nodeExtent2 !== oldNodeExtent || globalExtent !== oldGlobalExtent) {
        clampPosition2();
      }
    });
    if (node.extent === "parent" || typeof node.extent === "object" && "range" in node.extent && node.extent.range === "parent") {
      until(() => isInit).toBe(true).then(clampPosition2);
    } else {
      clampPosition2();
    }
    return () => {
      var _node$computedPositio, _node$computedPositio2;
      if (node.hidden) {
        return null;
      }
      return h("div", _extends({
        "ref": nodeElement,
        "data-id": node.id,
        "class": ["vue-flow__node", "vue-flow__node-" + (nodeCmp.value === false ? "default" : node.type || "default"), {
          [noPanClassName.value]: isDraggable.value,
          dragging: dragging == null ? void 0 : dragging.value,
          draggable: isDraggable.value,
          selected: node.selected,
          selectable: isSelectable.value,
          parent: node.isParent
        }, getClass.value],
        "style": _extends({
          visibility: isInit.value ? "visible" : "hidden",
          zIndex: (_node$computedPositio = node.computedPosition.z) != null ? _node$computedPositio : zIndex.value,
          transform: "translate(" + node.computedPosition.x + "px," + node.computedPosition.y + "px)",
          pointerEvents: hasPointerEvents.value ? "all" : "none"
        }, getStyle.value),
        "tabIndex": isFocusable.value ? 0 : void 0,
        "role": isFocusable.value ? "group" : void 0,
        "aria-describedby": disableKeyboardA11y.value ? void 0 : ARIA_NODE_DESC_KEY + "-" + vueFlowId,
        "aria-label": node.ariaLabel,
        "aria-roledescription": "node"
      }, node.domAttributes, {
        "onMouseenter": onMouseEnter,
        "onMousemove": onMouseMove,
        "onMouseleave": onMouseLeave,
        "onContextmenu": onContextMenu,
        "onClick": onSelectNode,
        "onDblclick": onDoubleClick,
        "onKeydown": onKeyDown
      }), [h(nodeCmp.value === false ? getNodeTypes.value.default : nodeCmp.value, {
        id: node.id,
        type: node.type,
        data: node.data,
        events: _extends({}, node.events, on),
        selected: node.selected,
        resizing: node.resizing,
        dragging: dragging.value,
        connectable: isConnectable.value,
        position: node.computedPosition,
        dimensions: node.dimensions,
        isValidTargetPos: node.isValidTargetPos,
        isValidSourcePos: node.isValidSourcePos,
        parent: node.parentNode,
        parentNodeId: node.parentNode,
        zIndex: (_node$computedPositio2 = node.computedPosition.z) != null ? _node$computedPositio2 : zIndex.value,
        targetPosition: node.targetPosition,
        sourcePosition: node.sourcePosition,
        label: node.label,
        dragHandle: node.dragHandle,
        onUpdateNodeInternals: updateInternals
      })]);
    };
    function clampPosition2() {
      const nextPosition = node.computedPosition;
      const _calcNextPosition3 = calcNextPosition(node, snapToGrid.value ? snapPosition(nextPosition, snapGrid.value) : nextPosition, emits.error, nodeExtent.value, parentNode.value),
        computedPosition = _calcNextPosition3.computedPosition,
        position = _calcNextPosition3.position;
      if (node.computedPosition.x !== computedPosition.x || node.computedPosition.y !== computedPosition.y) {
        node.computedPosition = _extends({}, node.computedPosition, computedPosition);
      }
      if (node.position.x !== position.x || node.position.y !== position.y) {
        node.position = position;
      }
    }
    function updateInternals() {
      if (nodeElement.value) {
        updateNodeDimensions([{
          id: props.id,
          nodeElement: nodeElement.value,
          forceUpdate: true
        }]);
      }
    }
    function onMouseEnter(event) {
      if (!(dragging == null ? void 0 : dragging.value)) {
        emit.mouseEnter({
          event,
          node
        });
      }
    }
    function onMouseMove(event) {
      if (!(dragging == null ? void 0 : dragging.value)) {
        emit.mouseMove({
          event,
          node
        });
      }
    }
    function onMouseLeave(event) {
      if (!(dragging == null ? void 0 : dragging.value)) {
        emit.mouseLeave({
          event,
          node
        });
      }
    }
    function onContextMenu(event) {
      return emit.contextMenu({
        event,
        node
      });
    }
    function onDoubleClick(event) {
      return emit.doubleClick({
        event,
        node
      });
    }
    function onSelectNode(event) {
      if (isSelectable.value && (!selectNodesOnDrag.value || !isDraggable.value || nodeDragThreshold.value > 0)) {
        handleNodeClick(node, multiSelectionActive.value, addSelectedNodes, removeSelectedNodes, nodesSelectionActive, false, nodeElement.value);
      }
      emit.click({
        event,
        node
      });
    }
    function onKeyDown(event) {
      if (isInputDOMNode(event) || disableKeyboardA11y.value) {
        return;
      }
      if (elementSelectionKeys.includes(event.key) && isSelectable.value) {
        const unselect = event.key === "Escape";
        handleNodeClick(node, multiSelectionActive.value, addSelectedNodes, removeSelectedNodes, nodesSelectionActive, unselect, nodeElement.value);
      } else if (isDraggable.value && node.selected && arrowKeyDiffs[event.key]) {
        event.preventDefault();
        ariaLiveMessage.value = "Moved selected node " + event.key.replace("Arrow", "").toLowerCase() + ". New position, x: " + ~~node.position.x + ", y: " + ~~node.position.y;
        updateNodePositions({
          x: arrowKeyDiffs[event.key].x,
          y: arrowKeyDiffs[event.key].y
        }, event.shiftKey);
      }
    }
  }
});
const NodeWrapper$1 = NodeWrapper;
const _hoisted_1$2$1 = {
  height: "0",
  width: "0"
};
const __default__$3$1 = {
  name: "EdgeLabelRenderer",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$3 = /* @__PURE__ */defineComponent(_extends({}, __default__$3$1, {
  setup(__props) {
    const _useVueFlow17 = useVueFlow(),
      viewportRef = _useVueFlow17.viewportRef;
    const teleportTarget = toRef(() => {
      var _a;
      return (_a = viewportRef.value) == null ? void 0 : _a.getElementsByClassName("vue-flow__edge-labels")[0];
    });
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("svg", null, [(openBlock(), createElementBlock("foreignObject", _hoisted_1$2$1, [(openBlock(), createBlock(Teleport, {
        to: teleportTarget.value,
        disabled: !teleportTarget.value
      }, [renderSlot(_ctx.$slots, "default")], 8, ["to", "disabled"]))]))]);
    };
  }
}));
function useNodesInitialized(options) {
  if (options === void 0) {
    options = {
      includeHiddenNodes: false
    };
  }
  const _useVueFlow18 = useVueFlow(),
    nodes = _useVueFlow18.nodes;
  return computed(() => {
    if (nodes.value.length === 0) {
      return false;
    }
    for (const node of nodes.value) {
      if (options.includeHiddenNodes || !node.hidden) {
        if ((node == null ? void 0 : node.handleBounds) === void 0 || node.dimensions.width === 0 || node.dimensions.height === 0) {
          return false;
        }
      }
    }
    return true;
  });
}
const _hoisted_1$1$2 = {
  class: "vue-flow__nodes vue-flow__container"
};
const __default__$2$1 = {
  name: "Nodes",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$2$1 = /* @__PURE__ */defineComponent(_extends({}, __default__$2$1, {
  setup(__props) {
    const _useVueFlow19 = useVueFlow(),
      getNodes = _useVueFlow19.getNodes,
      updateNodeDimensions = _useVueFlow19.updateNodeDimensions,
      emits = _useVueFlow19.emits;
    const nodesInitialized = useNodesInitialized();
    const resizeObserver = ref();
    watch(nodesInitialized, isInit => {
      if (isInit) {
        nextTick(() => {
          emits.nodesInitialized(getNodes.value);
        });
      }
    }, {
      immediate: true
    });
    onMounted(() => {
      resizeObserver.value = new ResizeObserver(entries => {
        const updates = entries.map(entry => {
          const id2 = entry.target.getAttribute("data-id");
          return {
            id: id2,
            nodeElement: entry.target,
            forceUpdate: true
          };
        });
        nextTick(() => updateNodeDimensions(updates));
      });
    });
    onBeforeUnmount(() => {
      var _a;
      return (_a = resizeObserver.value) == null ? void 0 : _a.disconnect();
    });
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", _hoisted_1$1$2, [resizeObserver.value ? (openBlock(true), createElementBlock(Fragment, {
        key: 0
      }, renderList(unref(getNodes), (node, __, ___, _cached) => {
        const _memo = [node.id];
        if (_cached && _cached.key === node.id && isMemoSame(_cached, _memo)) return _cached;
        const _item = (openBlock(), createBlock(unref(NodeWrapper$1), {
          id: node.id,
          key: node.id,
          "resize-observer": resizeObserver.value
        }, null, 8, ["id", "resize-observer"]));
        _item.memo = _memo;
        return _item;
      }, _cache, 0), 128)) : createCommentVNode("", true)]);
    };
  }
}));
function useStylesLoadedWarning() {
  const _useVueFlow20 = useVueFlow(),
    emits = _useVueFlow20.emits;
  onMounted(() => {
    if (isDev()) {
      const pane = document.querySelector(".vue-flow__pane");
      if (pane && !(window.getComputedStyle(pane).zIndex === "1")) {
        emits.error(new VueFlowError(ErrorCode.MISSING_STYLES));
      }
    }
  });
}
const _hoisted_1$a = /* @__PURE__ */createBaseVNode("div", {
  class: "vue-flow__edge-labels"
}, null, -1);
const __default__$1$1 = {
  name: "VueFlow",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$1$1 = /* @__PURE__ */defineComponent(_extends({}, __default__$1$1, {
  props: {
    id: {},
    modelValue: {},
    nodes: {},
    edges: {},
    edgeTypes: {},
    nodeTypes: {},
    connectionMode: {},
    connectionLineType: {},
    connectionLineStyle: {
      default: void 0
    },
    connectionLineOptions: {
      default: void 0
    },
    connectionRadius: {},
    isValidConnection: {
      type: [Function, null],
      default: void 0
    },
    deleteKeyCode: {
      default: void 0
    },
    selectionKeyCode: {
      type: [Boolean, null],
      default: void 0
    },
    multiSelectionKeyCode: {
      default: void 0
    },
    zoomActivationKeyCode: {
      default: void 0
    },
    panActivationKeyCode: {
      default: void 0
    },
    snapToGrid: {
      type: Boolean,
      default: void 0
    },
    snapGrid: {},
    onlyRenderVisibleElements: {
      type: Boolean,
      default: void 0
    },
    edgesUpdatable: {
      type: [Boolean, String],
      default: void 0
    },
    nodesDraggable: {
      type: Boolean,
      default: void 0
    },
    nodesConnectable: {
      type: Boolean,
      default: void 0
    },
    nodeDragThreshold: {},
    elementsSelectable: {
      type: Boolean,
      default: void 0
    },
    selectNodesOnDrag: {
      type: Boolean,
      default: void 0
    },
    panOnDrag: {
      type: [Boolean, Array],
      default: void 0
    },
    minZoom: {},
    maxZoom: {},
    defaultViewport: {},
    translateExtent: {},
    nodeExtent: {},
    defaultMarkerColor: {},
    zoomOnScroll: {
      type: Boolean,
      default: void 0
    },
    zoomOnPinch: {
      type: Boolean,
      default: void 0
    },
    panOnScroll: {
      type: Boolean,
      default: void 0
    },
    panOnScrollSpeed: {},
    panOnScrollMode: {},
    paneClickDistance: {},
    zoomOnDoubleClick: {
      type: Boolean,
      default: void 0
    },
    preventScrolling: {
      type: Boolean,
      default: void 0
    },
    selectionMode: {},
    edgeUpdaterRadius: {},
    fitViewOnInit: {
      type: Boolean,
      default: void 0
    },
    connectOnClick: {
      type: Boolean,
      default: void 0
    },
    applyDefault: {
      type: Boolean,
      default: void 0
    },
    autoConnect: {
      type: [Boolean, Function],
      default: void 0
    },
    noDragClassName: {},
    noWheelClassName: {},
    noPanClassName: {},
    defaultEdgeOptions: {},
    elevateEdgesOnSelect: {
      type: Boolean,
      default: void 0
    },
    elevateNodesOnSelect: {
      type: Boolean,
      default: void 0
    },
    disableKeyboardA11y: {
      type: Boolean,
      default: void 0
    },
    edgesFocusable: {
      type: Boolean,
      default: void 0
    },
    nodesFocusable: {
      type: Boolean,
      default: void 0
    },
    autoPanOnConnect: {
      type: Boolean,
      default: void 0
    },
    autoPanOnNodeDrag: {
      type: Boolean,
      default: void 0
    },
    autoPanSpeed: {}
  },
  emits: ["nodesChange", "edgesChange", "nodesInitialized", "paneReady", "init", "updateNodeInternals", "error", "connect", "connectStart", "connectEnd", "clickConnectStart", "clickConnectEnd", "moveStart", "move", "moveEnd", "selectionDragStart", "selectionDrag", "selectionDragStop", "selectionContextMenu", "selectionStart", "selectionEnd", "viewportChangeStart", "viewportChange", "viewportChangeEnd", "paneScroll", "paneClick", "paneContextMenu", "paneMouseEnter", "paneMouseMove", "paneMouseLeave", "edgeUpdate", "edgeContextMenu", "edgeMouseEnter", "edgeMouseMove", "edgeMouseLeave", "edgeDoubleClick", "edgeClick", "edgeUpdateStart", "edgeUpdateEnd", "nodeContextMenu", "nodeMouseEnter", "nodeMouseMove", "nodeMouseLeave", "nodeDoubleClick", "nodeClick", "nodeDragStart", "nodeDrag", "nodeDragStop", "miniMapNodeClick", "miniMapNodeDoubleClick", "miniMapNodeMouseEnter", "miniMapNodeMouseMove", "miniMapNodeMouseLeave", "update:modelValue", "update:nodes", "update:edges"],
  setup(__props, _ref97) {
    let __expose = _ref97.expose,
      emit = _ref97.emit;
    const props = __props;
    const slots = useSlots();
    const modelValue = useVModel(props, "modelValue", emit);
    const modelNodes = useVModel(props, "nodes", emit);
    const modelEdges = useVModel(props, "edges", emit);
    const vfInstance = useVueFlow(props);
    const disposeWatchers = useWatchProps({
      modelValue,
      nodes: modelNodes,
      edges: modelEdges
    }, props, vfInstance);
    useHooks(emit, vfInstance.hooks);
    useOnInitHandler();
    useStylesLoadedWarning();
    provide(Slots$1, slots);
    onUnmounted(disposeWatchers);
    __expose(vfInstance);
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        ref: unref(vfInstance).vueFlowRef,
        class: "vue-flow"
      }, [createVNode(_sfc_main$8, null, {
        default: withCtx(() => [createVNode(_sfc_main$4), _hoisted_1$a, createVNode(_sfc_main$2$1), renderSlot(_ctx.$slots, "zoom-pane")]),
        _: 3
      }), renderSlot(_ctx.$slots, "default"), createVNode(_sfc_main$7)], 512);
    };
  }
}));
const __default__$g = {
  name: "Panel",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$g = /* @__PURE__ */defineComponent(_extends({}, __default__$g, {
  props: {
    position: {}
  },
  setup(__props) {
    const props = __props;
    const _useVueFlow21 = useVueFlow(),
      userSelectionActive = _useVueFlow21.userSelectionActive;
    const positionClasses = computed(() => ("" + props.position).split("-"));
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: normalizeClass(["vue-flow__panel", positionClasses.value]),
        style: normalizeStyle({
          pointerEvents: unref(userSelectionActive) ? "none" : "all"
        })
      }, [renderSlot(_ctx.$slots, "default")], 6);
    };
  }
}));

var BackgroundVariant = /* @__PURE__ */(BackgroundVariant2 => {
  BackgroundVariant2["Lines"] = "lines";
  BackgroundVariant2["Dots"] = "dots";
  return BackgroundVariant2;
})(BackgroundVariant || {});
const LinePattern = function LinePattern(_ref) {
  let dimensions = _ref.dimensions,
    size = _ref.size,
    color = _ref.color;
  return h("path", {
    "stroke": color,
    "stroke-width": size,
    "d": "M" + dimensions[0] / 2 + " 0 V" + dimensions[1] + " M0 " + dimensions[1] / 2 + " H" + dimensions[0]
  });
};
const DotPattern = function DotPattern(_ref2) {
  let radius = _ref2.radius,
    color = _ref2.color;
  return h("circle", {
    cx: radius,
    cy: radius,
    r: radius,
    fill: color
  });
};
({
  [BackgroundVariant.Lines]: LinePattern,
  [BackgroundVariant.Dots]: DotPattern
});
const DefaultBgColors = {
  [BackgroundVariant.Dots]: "#81818a",
  [BackgroundVariant.Lines]: "#eee"
};
const _hoisted_1$7 = ["id", "x", "y", "width", "height", "patternTransform"];
const _hoisted_2$7 = {
  key: 2,
  height: "100",
  width: "100"
};
const _hoisted_3$7 = ["fill"];
const _hoisted_4$5 = ["x", "y", "fill"];
const __default__$3 = {
  name: "Background",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main$2 = /* @__PURE__ */defineComponent(_extends({}, __default__$3, {
  props: {
    id: {},
    variant: {
      default: () => BackgroundVariant.Dots
    },
    gap: {
      default: 20
    },
    size: {
      default: 1
    },
    lineWidth: {
      default: 1
    },
    patternColor: {},
    color: {},
    bgColor: {},
    height: {
      default: 100
    },
    width: {
      default: 100
    },
    x: {
      default: 0
    },
    y: {
      default: 0
    },
    offset: {
      default: 0
    }
  },
  setup(__props) {
    const _useVueFlow = useVueFlow(),
      vueFlowId = _useVueFlow.id,
      viewport = _useVueFlow.viewport;
    const background = computed(() => {
      const zoom = viewport.value.zoom;
      const _ref3 = Array.isArray(__props.gap) ? __props.gap : [__props.gap, __props.gap],
        gapX = _ref3[0],
        gapY = _ref3[1];
      const scaledGap = [gapX * zoom || 1, gapY * zoom || 1];
      const scaledSize = __props.size * zoom;
      const _ref4 = Array.isArray(__props.offset) ? __props.offset : [__props.offset, __props.offset],
        offsetX = _ref4[0],
        offsetY = _ref4[1];
      const scaledOffset = [offsetX * zoom || 1 + scaledGap[0] / 2, offsetY * zoom || 1 + scaledGap[1] / 2];
      return {
        scaledGap,
        offset: scaledOffset,
        size: scaledSize
      };
    });
    const patternId = toRef(() => "pattern-" + vueFlowId + (__props.id ? "-" + __props.id : ""));
    const patternColor = toRef(() => __props.color || __props.patternColor || DefaultBgColors[__props.variant || BackgroundVariant.Dots]);
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("svg", {
        class: "vue-flow__background vue-flow__container",
        style: normalizeStyle({
          height: (_ctx.height > 100 ? 100 : _ctx.height) + "%",
          width: (_ctx.width > 100 ? 100 : _ctx.width) + "%"
        })
      }, [renderSlot(_ctx.$slots, "pattern-container", {
        id: patternId.value
      }, () => [createBaseVNode("pattern", {
        id: patternId.value,
        x: unref(viewport).x % background.value.scaledGap[0],
        y: unref(viewport).y % background.value.scaledGap[1],
        width: background.value.scaledGap[0],
        height: background.value.scaledGap[1],
        patternTransform: "translate(-" + background.value.offset[0] + ",-" + background.value.offset[1] + ")",
        patternUnits: "userSpaceOnUse"
      }, [renderSlot(_ctx.$slots, "pattern", {}, () => [_ctx.variant === unref(BackgroundVariant).Lines ? (openBlock(), createBlock(unref(LinePattern), {
        key: 0,
        size: _ctx.lineWidth,
        color: patternColor.value,
        dimensions: background.value.scaledGap
      }, null, 8, ["size", "color", "dimensions"])) : _ctx.variant === unref(BackgroundVariant).Dots ? (openBlock(), createBlock(unref(DotPattern), {
        key: 1,
        color: patternColor.value,
        radius: background.value.size / 2
      }, null, 8, ["color", "radius"])) : createCommentVNode("", true), _ctx.bgColor ? (openBlock(), createElementBlock("svg", _hoisted_2$7, [createBaseVNode("rect", {
        width: "100%",
        height: "100%",
        fill: _ctx.bgColor
      }, null, 8, _hoisted_3$7)])) : createCommentVNode("", true)])], 8, _hoisted_1$7)]), createBaseVNode("rect", {
        x: _ctx.x,
        y: _ctx.y,
        width: "100%",
        height: "100%",
        fill: "url(#" + patternId.value + ")"
      }, null, 8, _hoisted_4$5), renderSlot(_ctx.$slots, "default", {
        id: patternId.value
      })], 4);
    };
  }
}));

var noop = {
  value: () => {}
};
function dispatch() {
  for (var i = 0, n = arguments.length, _ = {}, t; i < n; ++i) {
    if (!(t = arguments[i] + "") || t in _ || /[\s.]/.test(t)) throw new Error("illegal type: " + t);
    _[t] = [];
  }
  return new Dispatch(_);
}
function Dispatch(_) {
  this._ = _;
}
function parseTypenames$1(typenames, types) {
  return typenames.trim().split(/^|\s+/).map(function (t) {
    var name = "",
      i = t.indexOf(".");
    if (i >= 0) name = t.slice(i + 1), t = t.slice(0, i);
    if (t && !types.hasOwnProperty(t)) throw new Error("unknown type: " + t);
    return {
      type: t,
      name
    };
  });
}
Dispatch.prototype = dispatch.prototype = {
  constructor: Dispatch,
  on: function on(typename, callback) {
    var _ = this._,
      T = parseTypenames$1(typename + "", _),
      t,
      i = -1,
      n = T.length;
    if (arguments.length < 2) {
      while (++i < n) if ((t = (typename = T[i]).type) && (t = get$1(_[t], typename.name))) return t;
      return;
    }
    if (callback != null && typeof callback !== "function") throw new Error("invalid callback: " + callback);
    while (++i < n) {
      if (t = (typename = T[i]).type) _[t] = set$1(_[t], typename.name, callback);else if (callback == null) for (t in _) _[t] = set$1(_[t], typename.name, null);
    }
    return this;
  },
  copy: function copy() {
    var copy = {},
      _ = this._;
    for (var t in _) copy[t] = _[t].slice();
    return new Dispatch(copy);
  },
  call: function call(type, that) {
    if ((n = arguments.length - 2) > 0) for (var args = new Array(n), i = 0, n, t; i < n; ++i) args[i] = arguments[i + 2];
    if (!this._.hasOwnProperty(type)) throw new Error("unknown type: " + type);
    for (t = this._[type], i = 0, n = t.length; i < n; ++i) t[i].value.apply(that, args);
  },
  apply: function apply(type, that, args) {
    if (!this._.hasOwnProperty(type)) throw new Error("unknown type: " + type);
    for (var t = this._[type], i = 0, n = t.length; i < n; ++i) t[i].value.apply(that, args);
  }
};
function get$1(type, name) {
  for (var i = 0, n = type.length, c; i < n; ++i) {
    if ((c = type[i]).name === name) {
      return c.value;
    }
  }
}
function set$1(type, name, callback) {
  for (var i = 0, n = type.length; i < n; ++i) {
    if (type[i].name === name) {
      type[i] = noop, type = type.slice(0, i).concat(type.slice(i + 1));
      break;
    }
  }
  if (callback != null) type.push({
    name,
    value: callback
  });
  return type;
}
var xhtml = "http://www.w3.org/1999/xhtml";
const namespaces = {
  svg: "http://www.w3.org/2000/svg",
  xhtml,
  xlink: "http://www.w3.org/1999/xlink",
  xml: "http://www.w3.org/XML/1998/namespace",
  xmlns: "http://www.w3.org/2000/xmlns/"
};
function namespace(name) {
  var prefix = name += "",
    i = prefix.indexOf(":");
  if (i >= 0 && (prefix = name.slice(0, i)) !== "xmlns") name = name.slice(i + 1);
  return namespaces.hasOwnProperty(prefix) ? {
    space: namespaces[prefix],
    local: name
  } : name;
}
function creatorInherit(name) {
  return function () {
    var document2 = this.ownerDocument,
      uri = this.namespaceURI;
    return uri === xhtml && document2.documentElement.namespaceURI === xhtml ? document2.createElement(name) : document2.createElementNS(uri, name);
  };
}
function creatorFixed(fullname) {
  return function () {
    return this.ownerDocument.createElementNS(fullname.space, fullname.local);
  };
}
function creator(name) {
  var fullname = namespace(name);
  return (fullname.local ? creatorFixed : creatorInherit)(fullname);
}
function none() {}
function selector(selector2) {
  return selector2 == null ? none : function () {
    return this.querySelector(selector2);
  };
}
function selection_select(select2) {
  if (typeof select2 !== "function") select2 = selector(select2);
  for (var groups = this._groups, m = groups.length, subgroups = new Array(m), j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, subgroup = subgroups[j] = new Array(n), node, subnode, i = 0; i < n; ++i) {
      if ((node = group[i]) && (subnode = select2.call(node, node.__data__, i, group))) {
        if ("__data__" in node) subnode.__data__ = node.__data__;
        subgroup[i] = subnode;
      }
    }
  }
  return new Selection$1(subgroups, this._parents);
}
function array(x) {
  return x == null ? [] : Array.isArray(x) ? x : Array.from(x);
}
function empty() {
  return [];
}
function selectorAll(selector2) {
  return selector2 == null ? empty : function () {
    return this.querySelectorAll(selector2);
  };
}
function arrayAll(select2) {
  return function () {
    return array(select2.apply(this, arguments));
  };
}
function selection_selectAll(select2) {
  if (typeof select2 === "function") select2 = arrayAll(select2);else select2 = selectorAll(select2);
  for (var groups = this._groups, m = groups.length, subgroups = [], parents = [], j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, node, i = 0; i < n; ++i) {
      if (node = group[i]) {
        subgroups.push(select2.call(node, node.__data__, i, group));
        parents.push(node);
      }
    }
  }
  return new Selection$1(subgroups, parents);
}
function matcher(selector2) {
  return function () {
    return this.matches(selector2);
  };
}
function childMatcher(selector2) {
  return function (node) {
    return node.matches(selector2);
  };
}
var find = Array.prototype.find;
function childFind(match) {
  return function () {
    return find.call(this.children, match);
  };
}
function childFirst() {
  return this.firstElementChild;
}
function selection_selectChild(match) {
  return this.select(match == null ? childFirst : childFind(typeof match === "function" ? match : childMatcher(match)));
}
var filter = Array.prototype.filter;
function children() {
  return Array.from(this.children);
}
function childrenFilter(match) {
  return function () {
    return filter.call(this.children, match);
  };
}
function selection_selectChildren(match) {
  return this.selectAll(match == null ? children : childrenFilter(typeof match === "function" ? match : childMatcher(match)));
}
function selection_filter(match) {
  if (typeof match !== "function") match = matcher(match);
  for (var groups = this._groups, m = groups.length, subgroups = new Array(m), j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, subgroup = subgroups[j] = [], node, i = 0; i < n; ++i) {
      if ((node = group[i]) && match.call(node, node.__data__, i, group)) {
        subgroup.push(node);
      }
    }
  }
  return new Selection$1(subgroups, this._parents);
}
function sparse(update) {
  return new Array(update.length);
}
function selection_enter() {
  return new Selection$1(this._enter || this._groups.map(sparse), this._parents);
}
function EnterNode(parent, datum2) {
  this.ownerDocument = parent.ownerDocument;
  this.namespaceURI = parent.namespaceURI;
  this._next = null;
  this._parent = parent;
  this.__data__ = datum2;
}
EnterNode.prototype = {
  constructor: EnterNode,
  appendChild: function appendChild(child) {
    return this._parent.insertBefore(child, this._next);
  },
  insertBefore: function insertBefore(child, next) {
    return this._parent.insertBefore(child, next);
  },
  querySelector: function querySelector(selector2) {
    return this._parent.querySelector(selector2);
  },
  querySelectorAll: function querySelectorAll(selector2) {
    return this._parent.querySelectorAll(selector2);
  }
};
function constant$2(x) {
  return function () {
    return x;
  };
}
function bindIndex(parent, group, enter, update, exit, data) {
  var i = 0,
    node,
    groupLength = group.length,
    dataLength = data.length;
  for (; i < dataLength; ++i) {
    if (node = group[i]) {
      node.__data__ = data[i];
      update[i] = node;
    } else {
      enter[i] = new EnterNode(parent, data[i]);
    }
  }
  for (; i < groupLength; ++i) {
    if (node = group[i]) {
      exit[i] = node;
    }
  }
}
function bindKey(parent, group, enter, update, exit, data, key) {
  var i,
    node,
    nodeByKeyValue = /* @__PURE__ */new Map(),
    groupLength = group.length,
    dataLength = data.length,
    keyValues = new Array(groupLength),
    keyValue;
  for (i = 0; i < groupLength; ++i) {
    if (node = group[i]) {
      keyValues[i] = keyValue = key.call(node, node.__data__, i, group) + "";
      if (nodeByKeyValue.has(keyValue)) {
        exit[i] = node;
      } else {
        nodeByKeyValue.set(keyValue, node);
      }
    }
  }
  for (i = 0; i < dataLength; ++i) {
    keyValue = key.call(parent, data[i], i, data) + "";
    if (node = nodeByKeyValue.get(keyValue)) {
      update[i] = node;
      node.__data__ = data[i];
      nodeByKeyValue.delete(keyValue);
    } else {
      enter[i] = new EnterNode(parent, data[i]);
    }
  }
  for (i = 0; i < groupLength; ++i) {
    if ((node = group[i]) && nodeByKeyValue.get(keyValues[i]) === node) {
      exit[i] = node;
    }
  }
}
function datum(node) {
  return node.__data__;
}
function selection_data(value, key) {
  if (!arguments.length) return Array.from(this, datum);
  var bind = key ? bindKey : bindIndex,
    parents = this._parents,
    groups = this._groups;
  if (typeof value !== "function") value = constant$2(value);
  for (var m = groups.length, update = new Array(m), enter = new Array(m), exit = new Array(m), j = 0; j < m; ++j) {
    var parent = parents[j],
      group = groups[j],
      groupLength = group.length,
      data = arraylike(value.call(parent, parent && parent.__data__, j, parents)),
      dataLength = data.length,
      enterGroup = enter[j] = new Array(dataLength),
      updateGroup = update[j] = new Array(dataLength),
      exitGroup = exit[j] = new Array(groupLength);
    bind(parent, group, enterGroup, updateGroup, exitGroup, data, key);
    for (var i0 = 0, i1 = 0, previous, next; i0 < dataLength; ++i0) {
      if (previous = enterGroup[i0]) {
        if (i0 >= i1) i1 = i0 + 1;
        while (!(next = updateGroup[i1]) && ++i1 < dataLength);
        previous._next = next || null;
      }
    }
  }
  update = new Selection$1(update, parents);
  update._enter = enter;
  update._exit = exit;
  return update;
}
function arraylike(data) {
  return typeof data === "object" && "length" in data ? data : Array.from(data);
}
function selection_exit() {
  return new Selection$1(this._exit || this._groups.map(sparse), this._parents);
}
function selection_join(onenter, onupdate, onexit) {
  var enter = this.enter(),
    update = this,
    exit = this.exit();
  if (typeof onenter === "function") {
    enter = onenter(enter);
    if (enter) enter = enter.selection();
  } else {
    enter = enter.append(onenter + "");
  }
  if (onupdate != null) {
    update = onupdate(update);
    if (update) update = update.selection();
  }
  if (onexit == null) exit.remove();else onexit(exit);
  return enter && update ? enter.merge(update).order() : update;
}
function selection_merge(context) {
  var selection2 = context.selection ? context.selection() : context;
  for (var groups0 = this._groups, groups1 = selection2._groups, m0 = groups0.length, m1 = groups1.length, m = Math.min(m0, m1), merges = new Array(m0), j = 0; j < m; ++j) {
    for (var group0 = groups0[j], group1 = groups1[j], n = group0.length, merge = merges[j] = new Array(n), node, i = 0; i < n; ++i) {
      if (node = group0[i] || group1[i]) {
        merge[i] = node;
      }
    }
  }
  for (; j < m0; ++j) {
    merges[j] = groups0[j];
  }
  return new Selection$1(merges, this._parents);
}
function selection_order() {
  for (var groups = this._groups, j = -1, m = groups.length; ++j < m;) {
    for (var group = groups[j], i = group.length - 1, next = group[i], node; --i >= 0;) {
      if (node = group[i]) {
        if (next && node.compareDocumentPosition(next) ^ 4) next.parentNode.insertBefore(node, next);
        next = node;
      }
    }
  }
  return this;
}
function selection_sort(compare) {
  if (!compare) compare = ascending;
  function compareNode(a, b) {
    return a && b ? compare(a.__data__, b.__data__) : !a - !b;
  }
  for (var groups = this._groups, m = groups.length, sortgroups = new Array(m), j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, sortgroup = sortgroups[j] = new Array(n), node, i = 0; i < n; ++i) {
      if (node = group[i]) {
        sortgroup[i] = node;
      }
    }
    sortgroup.sort(compareNode);
  }
  return new Selection$1(sortgroups, this._parents).order();
}
function ascending(a, b) {
  return a < b ? -1 : a > b ? 1 : a >= b ? 0 : NaN;
}
function selection_call() {
  var callback = arguments[0];
  arguments[0] = this;
  callback.apply(null, arguments);
  return this;
}
function selection_nodes() {
  return Array.from(this);
}
function selection_node() {
  for (var groups = this._groups, j = 0, m = groups.length; j < m; ++j) {
    for (var group = groups[j], i = 0, n = group.length; i < n; ++i) {
      var node = group[i];
      if (node) return node;
    }
  }
  return null;
}
function selection_size() {
  let size = 0;
  for (const node of this) ++size;
  return size;
}
function selection_empty() {
  return !this.node();
}
function selection_each(callback) {
  for (var groups = this._groups, j = 0, m = groups.length; j < m; ++j) {
    for (var group = groups[j], i = 0, n = group.length, node; i < n; ++i) {
      if (node = group[i]) callback.call(node, node.__data__, i, group);
    }
  }
  return this;
}
function attrRemove$1(name) {
  return function () {
    this.removeAttribute(name);
  };
}
function attrRemoveNS$1(fullname) {
  return function () {
    this.removeAttributeNS(fullname.space, fullname.local);
  };
}
function attrConstant$1(name, value) {
  return function () {
    this.setAttribute(name, value);
  };
}
function attrConstantNS$1(fullname, value) {
  return function () {
    this.setAttributeNS(fullname.space, fullname.local, value);
  };
}
function attrFunction$1(name, value) {
  return function () {
    var v = value.apply(this, arguments);
    if (v == null) this.removeAttribute(name);else this.setAttribute(name, v);
  };
}
function attrFunctionNS$1(fullname, value) {
  return function () {
    var v = value.apply(this, arguments);
    if (v == null) this.removeAttributeNS(fullname.space, fullname.local);else this.setAttributeNS(fullname.space, fullname.local, v);
  };
}
function selection_attr(name, value) {
  var fullname = namespace(name);
  if (arguments.length < 2) {
    var node = this.node();
    return fullname.local ? node.getAttributeNS(fullname.space, fullname.local) : node.getAttribute(fullname);
  }
  return this.each((value == null ? fullname.local ? attrRemoveNS$1 : attrRemove$1 : typeof value === "function" ? fullname.local ? attrFunctionNS$1 : attrFunction$1 : fullname.local ? attrConstantNS$1 : attrConstant$1)(fullname, value));
}
function defaultView(node) {
  return node.ownerDocument && node.ownerDocument.defaultView || node.document && node || node.defaultView;
}
function styleRemove$1(name) {
  return function () {
    this.style.removeProperty(name);
  };
}
function styleConstant$1(name, value, priority) {
  return function () {
    this.style.setProperty(name, value, priority);
  };
}
function styleFunction$1(name, value, priority) {
  return function () {
    var v = value.apply(this, arguments);
    if (v == null) this.style.removeProperty(name);else this.style.setProperty(name, v, priority);
  };
}
function selection_style(name, value, priority) {
  return arguments.length > 1 ? this.each((value == null ? styleRemove$1 : typeof value === "function" ? styleFunction$1 : styleConstant$1)(name, value, priority == null ? "" : priority)) : styleValue(this.node(), name);
}
function styleValue(node, name) {
  return node.style.getPropertyValue(name) || defaultView(node).getComputedStyle(node, null).getPropertyValue(name);
}
function propertyRemove(name) {
  return function () {
    delete this[name];
  };
}
function propertyConstant(name, value) {
  return function () {
    this[name] = value;
  };
}
function propertyFunction(name, value) {
  return function () {
    var v = value.apply(this, arguments);
    if (v == null) delete this[name];else this[name] = v;
  };
}
function selection_property(name, value) {
  return arguments.length > 1 ? this.each((value == null ? propertyRemove : typeof value === "function" ? propertyFunction : propertyConstant)(name, value)) : this.node()[name];
}
function classArray(string) {
  return string.trim().split(/^|\s+/);
}
function classList(node) {
  return node.classList || new ClassList(node);
}
function ClassList(node) {
  this._node = node;
  this._names = classArray(node.getAttribute("class") || "");
}
ClassList.prototype = {
  add: function add(name) {
    var i = this._names.indexOf(name);
    if (i < 0) {
      this._names.push(name);
      this._node.setAttribute("class", this._names.join(" "));
    }
  },
  remove: function remove(name) {
    var i = this._names.indexOf(name);
    if (i >= 0) {
      this._names.splice(i, 1);
      this._node.setAttribute("class", this._names.join(" "));
    }
  },
  contains: function contains(name) {
    return this._names.indexOf(name) >= 0;
  }
};
function classedAdd(node, names) {
  var list = classList(node),
    i = -1,
    n = names.length;
  while (++i < n) list.add(names[i]);
}
function classedRemove(node, names) {
  var list = classList(node),
    i = -1,
    n = names.length;
  while (++i < n) list.remove(names[i]);
}
function classedTrue(names) {
  return function () {
    classedAdd(this, names);
  };
}
function classedFalse(names) {
  return function () {
    classedRemove(this, names);
  };
}
function classedFunction(names, value) {
  return function () {
    (value.apply(this, arguments) ? classedAdd : classedRemove)(this, names);
  };
}
function selection_classed(name, value) {
  var names = classArray(name + "");
  if (arguments.length < 2) {
    var list = classList(this.node()),
      i = -1,
      n = names.length;
    while (++i < n) if (!list.contains(names[i])) return false;
    return true;
  }
  return this.each((typeof value === "function" ? classedFunction : value ? classedTrue : classedFalse)(names, value));
}
function textRemove() {
  this.textContent = "";
}
function textConstant$1(value) {
  return function () {
    this.textContent = value;
  };
}
function textFunction$1(value) {
  return function () {
    var v = value.apply(this, arguments);
    this.textContent = v == null ? "" : v;
  };
}
function selection_text(value) {
  return arguments.length ? this.each(value == null ? textRemove : (typeof value === "function" ? textFunction$1 : textConstant$1)(value)) : this.node().textContent;
}
function htmlRemove() {
  this.innerHTML = "";
}
function htmlConstant(value) {
  return function () {
    this.innerHTML = value;
  };
}
function htmlFunction(value) {
  return function () {
    var v = value.apply(this, arguments);
    this.innerHTML = v == null ? "" : v;
  };
}
function selection_html(value) {
  return arguments.length ? this.each(value == null ? htmlRemove : (typeof value === "function" ? htmlFunction : htmlConstant)(value)) : this.node().innerHTML;
}
function raise() {
  if (this.nextSibling) this.parentNode.appendChild(this);
}
function selection_raise() {
  return this.each(raise);
}
function lower() {
  if (this.previousSibling) this.parentNode.insertBefore(this, this.parentNode.firstChild);
}
function selection_lower() {
  return this.each(lower);
}
function selection_append(name) {
  var create2 = typeof name === "function" ? name : creator(name);
  return this.select(function () {
    return this.appendChild(create2.apply(this, arguments));
  });
}
function constantNull() {
  return null;
}
function selection_insert(name, before) {
  var create2 = typeof name === "function" ? name : creator(name),
    select2 = before == null ? constantNull : typeof before === "function" ? before : selector(before);
  return this.select(function () {
    return this.insertBefore(create2.apply(this, arguments), select2.apply(this, arguments) || null);
  });
}
function remove() {
  var parent = this.parentNode;
  if (parent) parent.removeChild(this);
}
function selection_remove() {
  return this.each(remove);
}
function selection_cloneShallow() {
  var clone = this.cloneNode(false),
    parent = this.parentNode;
  return parent ? parent.insertBefore(clone, this.nextSibling) : clone;
}
function selection_cloneDeep() {
  var clone = this.cloneNode(true),
    parent = this.parentNode;
  return parent ? parent.insertBefore(clone, this.nextSibling) : clone;
}
function selection_clone(deep) {
  return this.select(deep ? selection_cloneDeep : selection_cloneShallow);
}
function selection_datum(value) {
  return arguments.length ? this.property("__data__", value) : this.node().__data__;
}
function contextListener(listener) {
  return function (event) {
    listener.call(this, event, this.__data__);
  };
}
function parseTypenames(typenames) {
  return typenames.trim().split(/^|\s+/).map(function (t) {
    var name = "",
      i = t.indexOf(".");
    if (i >= 0) name = t.slice(i + 1), t = t.slice(0, i);
    return {
      type: t,
      name
    };
  });
}
function onRemove(typename) {
  return function () {
    var on = this.__on;
    if (!on) return;
    for (var j = 0, i = -1, m = on.length, o; j < m; ++j) {
      if (o = on[j], (!typename.type || o.type === typename.type) && o.name === typename.name) {
        this.removeEventListener(o.type, o.listener, o.options);
      } else {
        on[++i] = o;
      }
    }
    if (++i) on.length = i;else delete this.__on;
  };
}
function onAdd(typename, value, options) {
  return function () {
    var on = this.__on,
      o,
      listener = contextListener(value);
    if (on) for (var j = 0, m = on.length; j < m; ++j) {
      if ((o = on[j]).type === typename.type && o.name === typename.name) {
        this.removeEventListener(o.type, o.listener, o.options);
        this.addEventListener(o.type, o.listener = listener, o.options = options);
        o.value = value;
        return;
      }
    }
    this.addEventListener(typename.type, listener, options);
    o = {
      type: typename.type,
      name: typename.name,
      value,
      listener,
      options
    };
    if (!on) this.__on = [o];else on.push(o);
  };
}
function selection_on(typename, value, options) {
  var typenames = parseTypenames(typename + ""),
    i,
    n = typenames.length,
    t;
  if (arguments.length < 2) {
    var on = this.node().__on;
    if (on) for (var j = 0, m = on.length, o; j < m; ++j) {
      for (i = 0, o = on[j]; i < n; ++i) {
        if ((t = typenames[i]).type === o.type && t.name === o.name) {
          return o.value;
        }
      }
    }
    return;
  }
  on = value ? onAdd : onRemove;
  for (i = 0; i < n; ++i) this.each(on(typenames[i], value, options));
  return this;
}
function dispatchEvent(node, type, params) {
  var window2 = defaultView(node),
    event = window2.CustomEvent;
  if (typeof event === "function") {
    event = new event(type, params);
  } else {
    event = window2.document.createEvent("Event");
    if (params) event.initEvent(type, params.bubbles, params.cancelable), event.detail = params.detail;else event.initEvent(type, false, false);
  }
  node.dispatchEvent(event);
}
function dispatchConstant(type, params) {
  return function () {
    return dispatchEvent(this, type, params);
  };
}
function dispatchFunction(type, params) {
  return function () {
    return dispatchEvent(this, type, params.apply(this, arguments));
  };
}
function selection_dispatch(type, params) {
  return this.each((typeof params === "function" ? dispatchFunction : dispatchConstant)(type, params));
}
function* selection_iterator() {
  for (var groups = this._groups, j = 0, m = groups.length; j < m; ++j) {
    for (var group = groups[j], i = 0, n = group.length, node; i < n; ++i) {
      if (node = group[i]) yield node;
    }
  }
}
var root = [null];
function Selection$1(groups, parents) {
  this._groups = groups;
  this._parents = parents;
}
function selection() {
  return new Selection$1([[document.documentElement]], root);
}
function selection_selection() {
  return this;
}
Selection$1.prototype = selection.prototype = {
  constructor: Selection$1,
  select: selection_select,
  selectAll: selection_selectAll,
  selectChild: selection_selectChild,
  selectChildren: selection_selectChildren,
  filter: selection_filter,
  data: selection_data,
  enter: selection_enter,
  exit: selection_exit,
  join: selection_join,
  merge: selection_merge,
  selection: selection_selection,
  order: selection_order,
  sort: selection_sort,
  call: selection_call,
  nodes: selection_nodes,
  node: selection_node,
  size: selection_size,
  empty: selection_empty,
  each: selection_each,
  attr: selection_attr,
  style: selection_style,
  property: selection_property,
  classed: selection_classed,
  text: selection_text,
  html: selection_html,
  raise: selection_raise,
  lower: selection_lower,
  append: selection_append,
  insert: selection_insert,
  remove: selection_remove,
  clone: selection_clone,
  datum: selection_datum,
  on: selection_on,
  dispatch: selection_dispatch,
  [Symbol.iterator]: selection_iterator
};
function select(selector2) {
  return typeof selector2 === "string" ? new Selection$1([[document.querySelector(selector2)]], [document.documentElement]) : new Selection$1([[selector2]], root);
}
function sourceEvent(event) {
  let sourceEvent2;
  while (sourceEvent2 = event.sourceEvent) event = sourceEvent2;
  return event;
}
function pointer(event, node) {
  event = sourceEvent(event);
  if (node === void 0) node = event.currentTarget;
  if (node) {
    var svg = node.ownerSVGElement || node;
    if (svg.createSVGPoint) {
      var point = svg.createSVGPoint();
      point.x = event.clientX, point.y = event.clientY;
      point = point.matrixTransform(node.getScreenCTM().inverse());
      return [point.x, point.y];
    }
    if (node.getBoundingClientRect) {
      var rect = node.getBoundingClientRect();
      return [event.clientX - rect.left - node.clientLeft, event.clientY - rect.top - node.clientTop];
    }
  }
  return [event.pageX, event.pageY];
}
const nonpassivecapture = {
  capture: true,
  passive: false
};
function noevent$1(event) {
  event.preventDefault();
  event.stopImmediatePropagation();
}
function dragDisable(view) {
  var root2 = view.document.documentElement,
    selection2 = select(view).on("dragstart.drag", noevent$1, nonpassivecapture);
  if ("onselectstart" in root2) {
    selection2.on("selectstart.drag", noevent$1, nonpassivecapture);
  } else {
    root2.__noselect = root2.style.MozUserSelect;
    root2.style.MozUserSelect = "none";
  }
}
function yesdrag(view, noclick) {
  var root2 = view.document.documentElement,
    selection2 = select(view).on("dragstart.drag", null);
  if (noclick) {
    selection2.on("click.drag", noevent$1, nonpassivecapture);
    setTimeout(function () {
      selection2.on("click.drag", null);
    }, 0);
  }
  if ("onselectstart" in root2) {
    selection2.on("selectstart.drag", null);
  } else {
    root2.style.MozUserSelect = root2.__noselect;
    delete root2.__noselect;
  }
}
function define(constructor, factory, prototype) {
  constructor.prototype = factory.prototype = prototype;
  prototype.constructor = constructor;
}
function extend(parent, definition) {
  var prototype = Object.create(parent.prototype);
  for (var key in definition) prototype[key] = definition[key];
  return prototype;
}
function Color() {}
var darker = 0.7;
var brighter = 1 / darker;
var reI = "\\s*([+-]?\\d+)\\s*",
  reN = "\\s*([+-]?(?:\\d*\\.)?\\d+(?:[eE][+-]?\\d+)?)\\s*",
  reP = "\\s*([+-]?(?:\\d*\\.)?\\d+(?:[eE][+-]?\\d+)?)%\\s*",
  reHex = /^#([0-9a-f]{3,8})$/,
  reRgbInteger = new RegExp("^rgb\\(" + reI + "," + reI + "," + reI + "\\)$"),
  reRgbPercent = new RegExp("^rgb\\(" + reP + "," + reP + "," + reP + "\\)$"),
  reRgbaInteger = new RegExp("^rgba\\(" + reI + "," + reI + "," + reI + "," + reN + "\\)$"),
  reRgbaPercent = new RegExp("^rgba\\(" + reP + "," + reP + "," + reP + "," + reN + "\\)$"),
  reHslPercent = new RegExp("^hsl\\(" + reN + "," + reP + "," + reP + "\\)$"),
  reHslaPercent = new RegExp("^hsla\\(" + reN + "," + reP + "," + reP + "," + reN + "\\)$");
var named = {
  aliceblue: 15792383,
  antiquewhite: 16444375,
  aqua: 65535,
  aquamarine: 8388564,
  azure: 15794175,
  beige: 16119260,
  bisque: 16770244,
  black: 0,
  blanchedalmond: 16772045,
  blue: 255,
  blueviolet: 9055202,
  brown: 10824234,
  burlywood: 14596231,
  cadetblue: 6266528,
  chartreuse: 8388352,
  chocolate: 13789470,
  coral: 16744272,
  cornflowerblue: 6591981,
  cornsilk: 16775388,
  crimson: 14423100,
  cyan: 65535,
  darkblue: 139,
  darkcyan: 35723,
  darkgoldenrod: 12092939,
  darkgray: 11119017,
  darkgreen: 25600,
  darkgrey: 11119017,
  darkkhaki: 12433259,
  darkmagenta: 9109643,
  darkolivegreen: 5597999,
  darkorange: 16747520,
  darkorchid: 10040012,
  darkred: 9109504,
  darksalmon: 15308410,
  darkseagreen: 9419919,
  darkslateblue: 4734347,
  darkslategray: 3100495,
  darkslategrey: 3100495,
  darkturquoise: 52945,
  darkviolet: 9699539,
  deeppink: 16716947,
  deepskyblue: 49151,
  dimgray: 6908265,
  dimgrey: 6908265,
  dodgerblue: 2003199,
  firebrick: 11674146,
  floralwhite: 16775920,
  forestgreen: 2263842,
  fuchsia: 16711935,
  gainsboro: 14474460,
  ghostwhite: 16316671,
  gold: 16766720,
  goldenrod: 14329120,
  gray: 8421504,
  green: 32768,
  greenyellow: 11403055,
  grey: 8421504,
  honeydew: 15794160,
  hotpink: 16738740,
  indianred: 13458524,
  indigo: 4915330,
  ivory: 16777200,
  khaki: 15787660,
  lavender: 15132410,
  lavenderblush: 16773365,
  lawngreen: 8190976,
  lemonchiffon: 16775885,
  lightblue: 11393254,
  lightcoral: 15761536,
  lightcyan: 14745599,
  lightgoldenrodyellow: 16448210,
  lightgray: 13882323,
  lightgreen: 9498256,
  lightgrey: 13882323,
  lightpink: 16758465,
  lightsalmon: 16752762,
  lightseagreen: 2142890,
  lightskyblue: 8900346,
  lightslategray: 7833753,
  lightslategrey: 7833753,
  lightsteelblue: 11584734,
  lightyellow: 16777184,
  lime: 65280,
  limegreen: 3329330,
  linen: 16445670,
  magenta: 16711935,
  maroon: 8388608,
  mediumaquamarine: 6737322,
  mediumblue: 205,
  mediumorchid: 12211667,
  mediumpurple: 9662683,
  mediumseagreen: 3978097,
  mediumslateblue: 8087790,
  mediumspringgreen: 64154,
  mediumturquoise: 4772300,
  mediumvioletred: 13047173,
  midnightblue: 1644912,
  mintcream: 16121850,
  mistyrose: 16770273,
  moccasin: 16770229,
  navajowhite: 16768685,
  navy: 128,
  oldlace: 16643558,
  olive: 8421376,
  olivedrab: 7048739,
  orange: 16753920,
  orangered: 16729344,
  orchid: 14315734,
  palegoldenrod: 15657130,
  palegreen: 10025880,
  paleturquoise: 11529966,
  palevioletred: 14381203,
  papayawhip: 16773077,
  peachpuff: 16767673,
  peru: 13468991,
  pink: 16761035,
  plum: 14524637,
  powderblue: 11591910,
  purple: 8388736,
  rebeccapurple: 6697881,
  red: 16711680,
  rosybrown: 12357519,
  royalblue: 4286945,
  saddlebrown: 9127187,
  salmon: 16416882,
  sandybrown: 16032864,
  seagreen: 3050327,
  seashell: 16774638,
  sienna: 10506797,
  silver: 12632256,
  skyblue: 8900331,
  slateblue: 6970061,
  slategray: 7372944,
  slategrey: 7372944,
  snow: 16775930,
  springgreen: 65407,
  steelblue: 4620980,
  tan: 13808780,
  teal: 32896,
  thistle: 14204888,
  tomato: 16737095,
  turquoise: 4251856,
  violet: 15631086,
  wheat: 16113331,
  white: 16777215,
  whitesmoke: 16119285,
  yellow: 16776960,
  yellowgreen: 10145074
};
define(Color, color, {
  copy(channels) {
    return Object.assign(new this.constructor(), this, channels);
  },
  displayable() {
    return this.rgb().displayable();
  },
  hex: color_formatHex,
  // Deprecated! Use color.formatHex.
  formatHex: color_formatHex,
  formatHex8: color_formatHex8,
  formatHsl: color_formatHsl,
  formatRgb: color_formatRgb,
  toString: color_formatRgb
});
function color_formatHex() {
  return this.rgb().formatHex();
}
function color_formatHex8() {
  return this.rgb().formatHex8();
}
function color_formatHsl() {
  return hslConvert(this).formatHsl();
}
function color_formatRgb() {
  return this.rgb().formatRgb();
}
function color(format) {
  var m, l;
  format = (format + "").trim().toLowerCase();
  return (m = reHex.exec(format)) ? (l = m[1].length, m = parseInt(m[1], 16), l === 6 ? rgbn(m) : l === 3 ? new Rgb(m >> 8 & 15 | m >> 4 & 240, m >> 4 & 15 | m & 240, (m & 15) << 4 | m & 15, 1) : l === 8 ? rgba(m >> 24 & 255, m >> 16 & 255, m >> 8 & 255, (m & 255) / 255) : l === 4 ? rgba(m >> 12 & 15 | m >> 8 & 240, m >> 8 & 15 | m >> 4 & 240, m >> 4 & 15 | m & 240, ((m & 15) << 4 | m & 15) / 255) : null) : (m = reRgbInteger.exec(format)) ? new Rgb(m[1], m[2], m[3], 1) : (m = reRgbPercent.exec(format)) ? new Rgb(m[1] * 255 / 100, m[2] * 255 / 100, m[3] * 255 / 100, 1) : (m = reRgbaInteger.exec(format)) ? rgba(m[1], m[2], m[3], m[4]) : (m = reRgbaPercent.exec(format)) ? rgba(m[1] * 255 / 100, m[2] * 255 / 100, m[3] * 255 / 100, m[4]) : (m = reHslPercent.exec(format)) ? hsla(m[1], m[2] / 100, m[3] / 100, 1) : (m = reHslaPercent.exec(format)) ? hsla(m[1], m[2] / 100, m[3] / 100, m[4]) : named.hasOwnProperty(format) ? rgbn(named[format]) : format === "transparent" ? new Rgb(NaN, NaN, NaN, 0) : null;
}
function rgbn(n) {
  return new Rgb(n >> 16 & 255, n >> 8 & 255, n & 255, 1);
}
function rgba(r, g, b, a) {
  if (a <= 0) r = g = b = NaN;
  return new Rgb(r, g, b, a);
}
function rgbConvert(o) {
  if (!(o instanceof Color)) o = color(o);
  if (!o) return new Rgb();
  o = o.rgb();
  return new Rgb(o.r, o.g, o.b, o.opacity);
}
function rgb(r, g, b, opacity) {
  return arguments.length === 1 ? rgbConvert(r) : new Rgb(r, g, b, opacity == null ? 1 : opacity);
}
function Rgb(r, g, b, opacity) {
  this.r = +r;
  this.g = +g;
  this.b = +b;
  this.opacity = +opacity;
}
define(Rgb, rgb, extend(Color, {
  brighter(k) {
    k = k == null ? brighter : Math.pow(brighter, k);
    return new Rgb(this.r * k, this.g * k, this.b * k, this.opacity);
  },
  darker(k) {
    k = k == null ? darker : Math.pow(darker, k);
    return new Rgb(this.r * k, this.g * k, this.b * k, this.opacity);
  },
  rgb() {
    return this;
  },
  clamp() {
    return new Rgb(clampi(this.r), clampi(this.g), clampi(this.b), clampa(this.opacity));
  },
  displayable() {
    return -0.5 <= this.r && this.r < 255.5 && -0.5 <= this.g && this.g < 255.5 && -0.5 <= this.b && this.b < 255.5 && 0 <= this.opacity && this.opacity <= 1;
  },
  hex: rgb_formatHex,
  // Deprecated! Use color.formatHex.
  formatHex: rgb_formatHex,
  formatHex8: rgb_formatHex8,
  formatRgb: rgb_formatRgb,
  toString: rgb_formatRgb
}));
function rgb_formatHex() {
  return "#" + hex(this.r) + hex(this.g) + hex(this.b);
}
function rgb_formatHex8() {
  return "#" + hex(this.r) + hex(this.g) + hex(this.b) + hex((isNaN(this.opacity) ? 1 : this.opacity) * 255);
}
function rgb_formatRgb() {
  const a = clampa(this.opacity);
  return "" + (a === 1 ? "rgb(" : "rgba(") + clampi(this.r) + ", " + clampi(this.g) + ", " + clampi(this.b) + (a === 1 ? ")" : ", " + a + ")");
}
function clampa(opacity) {
  return isNaN(opacity) ? 1 : Math.max(0, Math.min(1, opacity));
}
function clampi(value) {
  return Math.max(0, Math.min(255, Math.round(value) || 0));
}
function hex(value) {
  value = clampi(value);
  return (value < 16 ? "0" : "") + value.toString(16);
}
function hsla(h, s, l, a) {
  if (a <= 0) h = s = l = NaN;else if (l <= 0 || l >= 1) h = s = NaN;else if (s <= 0) h = NaN;
  return new Hsl(h, s, l, a);
}
function hslConvert(o) {
  if (o instanceof Hsl) return new Hsl(o.h, o.s, o.l, o.opacity);
  if (!(o instanceof Color)) o = color(o);
  if (!o) return new Hsl();
  if (o instanceof Hsl) return o;
  o = o.rgb();
  var r = o.r / 255,
    g = o.g / 255,
    b = o.b / 255,
    min = Math.min(r, g, b),
    max = Math.max(r, g, b),
    h = NaN,
    s = max - min,
    l = (max + min) / 2;
  if (s) {
    if (r === max) h = (g - b) / s + (g < b) * 6;else if (g === max) h = (b - r) / s + 2;else h = (r - g) / s + 4;
    s /= l < 0.5 ? max + min : 2 - max - min;
    h *= 60;
  } else {
    s = l > 0 && l < 1 ? 0 : h;
  }
  return new Hsl(h, s, l, o.opacity);
}
function hsl(h, s, l, opacity) {
  return arguments.length === 1 ? hslConvert(h) : new Hsl(h, s, l, opacity == null ? 1 : opacity);
}
function Hsl(h, s, l, opacity) {
  this.h = +h;
  this.s = +s;
  this.l = +l;
  this.opacity = +opacity;
}
define(Hsl, hsl, extend(Color, {
  brighter(k) {
    k = k == null ? brighter : Math.pow(brighter, k);
    return new Hsl(this.h, this.s, this.l * k, this.opacity);
  },
  darker(k) {
    k = k == null ? darker : Math.pow(darker, k);
    return new Hsl(this.h, this.s, this.l * k, this.opacity);
  },
  rgb() {
    var h = this.h % 360 + (this.h < 0) * 360,
      s = isNaN(h) || isNaN(this.s) ? 0 : this.s,
      l = this.l,
      m2 = l + (l < 0.5 ? l : 1 - l) * s,
      m1 = 2 * l - m2;
    return new Rgb(hsl2rgb(h >= 240 ? h - 240 : h + 120, m1, m2), hsl2rgb(h, m1, m2), hsl2rgb(h < 120 ? h + 240 : h - 120, m1, m2), this.opacity);
  },
  clamp() {
    return new Hsl(clamph(this.h), clampt(this.s), clampt(this.l), clampa(this.opacity));
  },
  displayable() {
    return (0 <= this.s && this.s <= 1 || isNaN(this.s)) && 0 <= this.l && this.l <= 1 && 0 <= this.opacity && this.opacity <= 1;
  },
  formatHsl() {
    const a = clampa(this.opacity);
    return "" + (a === 1 ? "hsl(" : "hsla(") + clamph(this.h) + ", " + clampt(this.s) * 100 + "%, " + clampt(this.l) * 100 + "%" + (a === 1 ? ")" : ", " + a + ")");
  }
}));
function clamph(value) {
  value = (value || 0) % 360;
  return value < 0 ? value + 360 : value;
}
function clampt(value) {
  return Math.max(0, Math.min(1, value || 0));
}
function hsl2rgb(h, m1, m2) {
  return (h < 60 ? m1 + (m2 - m1) * h / 60 : h < 180 ? m2 : h < 240 ? m1 + (m2 - m1) * (240 - h) / 60 : m1) * 255;
}
const constant$1 = x => () => x;
function linear(a, d) {
  return function (t) {
    return a + t * d;
  };
}
function exponential(a, b, y) {
  return a = Math.pow(a, y), b = Math.pow(b, y) - a, y = 1 / y, function (t) {
    return Math.pow(a + t * b, y);
  };
}
function gamma(y) {
  return (y = +y) === 1 ? nogamma : function (a, b) {
    return b - a ? exponential(a, b, y) : constant$1(isNaN(a) ? b : a);
  };
}
function nogamma(a, b) {
  var d = b - a;
  return d ? linear(a, d) : constant$1(isNaN(a) ? b : a);
}
const interpolateRgb = function rgbGamma(y) {
  var color2 = gamma(y);
  function rgb$1(start2, end) {
    var r = color2((start2 = rgb(start2)).r, (end = rgb(end)).r),
      g = color2(start2.g, end.g),
      b = color2(start2.b, end.b),
      opacity = nogamma(start2.opacity, end.opacity);
    return function (t) {
      start2.r = r(t);
      start2.g = g(t);
      start2.b = b(t);
      start2.opacity = opacity(t);
      return start2 + "";
    };
  }
  rgb$1.gamma = rgbGamma;
  return rgb$1;
}(1);
function interpolateNumber(a, b) {
  return a = +a, b = +b, function (t) {
    return a * (1 - t) + b * t;
  };
}
var reA = /[-+]?(?:\d+\.?\d*|\.?\d+)(?:[eE][-+]?\d+)?/g,
  reB = new RegExp(reA.source, "g");
function zero(b) {
  return function () {
    return b;
  };
}
function one(b) {
  return function (t) {
    return b(t) + "";
  };
}
function interpolateString(a, b) {
  var bi = reA.lastIndex = reB.lastIndex = 0,
    am,
    bm,
    bs,
    i = -1,
    s = [],
    q = [];
  a = a + "", b = b + "";
  while ((am = reA.exec(a)) && (bm = reB.exec(b))) {
    if ((bs = bm.index) > bi) {
      bs = b.slice(bi, bs);
      if (s[i]) s[i] += bs;else s[++i] = bs;
    }
    if ((am = am[0]) === (bm = bm[0])) {
      if (s[i]) s[i] += bm;else s[++i] = bm;
    } else {
      s[++i] = null;
      q.push({
        i,
        x: interpolateNumber(am, bm)
      });
    }
    bi = reB.lastIndex;
  }
  if (bi < b.length) {
    bs = b.slice(bi);
    if (s[i]) s[i] += bs;else s[++i] = bs;
  }
  return s.length < 2 ? q[0] ? one(q[0].x) : zero(b) : (b = q.length, function (t) {
    for (var i2 = 0, o; i2 < b; ++i2) s[(o = q[i2]).i] = o.x(t);
    return s.join("");
  });
}
var degrees = 180 / Math.PI;
var identity$1 = {
  translateX: 0,
  translateY: 0,
  rotate: 0,
  skewX: 0,
  scaleX: 1,
  scaleY: 1
};
function decompose(a, b, c, d, e, f) {
  var scaleX, scaleY, skewX;
  if (scaleX = Math.sqrt(a * a + b * b)) a /= scaleX, b /= scaleX;
  if (skewX = a * c + b * d) c -= a * skewX, d -= b * skewX;
  if (scaleY = Math.sqrt(c * c + d * d)) c /= scaleY, d /= scaleY, skewX /= scaleY;
  if (a * d < b * c) a = -a, b = -b, skewX = -skewX, scaleX = -scaleX;
  return {
    translateX: e,
    translateY: f,
    rotate: Math.atan2(b, a) * degrees,
    skewX: Math.atan(skewX) * degrees,
    scaleX,
    scaleY
  };
}
var svgNode;
function parseCss(value) {
  const m = new (typeof DOMMatrix === "function" ? DOMMatrix : WebKitCSSMatrix)(value + "");
  return m.isIdentity ? identity$1 : decompose(m.a, m.b, m.c, m.d, m.e, m.f);
}
function parseSvg(value) {
  if (value == null) return identity$1;
  if (!svgNode) svgNode = document.createElementNS("http://www.w3.org/2000/svg", "g");
  svgNode.setAttribute("transform", value);
  if (!(value = svgNode.transform.baseVal.consolidate())) return identity$1;
  value = value.matrix;
  return decompose(value.a, value.b, value.c, value.d, value.e, value.f);
}
function interpolateTransform(parse, pxComma, pxParen, degParen) {
  function pop(s) {
    return s.length ? s.pop() + " " : "";
  }
  function translate(xa, ya, xb, yb, s, q) {
    if (xa !== xb || ya !== yb) {
      var i = s.push("translate(", null, pxComma, null, pxParen);
      q.push({
        i: i - 4,
        x: interpolateNumber(xa, xb)
      }, {
        i: i - 2,
        x: interpolateNumber(ya, yb)
      });
    } else if (xb || yb) {
      s.push("translate(" + xb + pxComma + yb + pxParen);
    }
  }
  function rotate(a, b, s, q) {
    if (a !== b) {
      if (a - b > 180) b += 360;else if (b - a > 180) a += 360;
      q.push({
        i: s.push(pop(s) + "rotate(", null, degParen) - 2,
        x: interpolateNumber(a, b)
      });
    } else if (b) {
      s.push(pop(s) + "rotate(" + b + degParen);
    }
  }
  function skewX(a, b, s, q) {
    if (a !== b) {
      q.push({
        i: s.push(pop(s) + "skewX(", null, degParen) - 2,
        x: interpolateNumber(a, b)
      });
    } else if (b) {
      s.push(pop(s) + "skewX(" + b + degParen);
    }
  }
  function scale(xa, ya, xb, yb, s, q) {
    if (xa !== xb || ya !== yb) {
      var i = s.push(pop(s) + "scale(", null, ",", null, ")");
      q.push({
        i: i - 4,
        x: interpolateNumber(xa, xb)
      }, {
        i: i - 2,
        x: interpolateNumber(ya, yb)
      });
    } else if (xb !== 1 || yb !== 1) {
      s.push(pop(s) + "scale(" + xb + "," + yb + ")");
    }
  }
  return function (a, b) {
    var s = [],
      q = [];
    a = parse(a), b = parse(b);
    translate(a.translateX, a.translateY, b.translateX, b.translateY, s, q);
    rotate(a.rotate, b.rotate, s, q);
    skewX(a.skewX, b.skewX, s, q);
    scale(a.scaleX, a.scaleY, b.scaleX, b.scaleY, s, q);
    a = b = null;
    return function (t) {
      var i = -1,
        n = q.length,
        o;
      while (++i < n) s[(o = q[i]).i] = o.x(t);
      return s.join("");
    };
  };
}
var interpolateTransformCss = interpolateTransform(parseCss, "px, ", "px)", "deg)");
var interpolateTransformSvg = interpolateTransform(parseSvg, ", ", ")", ")");
var epsilon2 = 1e-12;
function cosh(x) {
  return ((x = Math.exp(x)) + 1 / x) / 2;
}
function sinh(x) {
  return ((x = Math.exp(x)) - 1 / x) / 2;
}
function tanh(x) {
  return ((x = Math.exp(2 * x)) - 1) / (x + 1);
}
const interpolateZoom = function zoomRho(rho, rho2, rho4) {
  function zoom2(p0, p1) {
    var ux0 = p0[0],
      uy0 = p0[1],
      w0 = p0[2],
      ux1 = p1[0],
      uy1 = p1[1],
      w1 = p1[2],
      dx = ux1 - ux0,
      dy = uy1 - uy0,
      d2 = dx * dx + dy * dy,
      i,
      S;
    if (d2 < epsilon2) {
      S = Math.log(w1 / w0) / rho;
      i = function i(t) {
        return [ux0 + t * dx, uy0 + t * dy, w0 * Math.exp(rho * t * S)];
      };
    } else {
      var d1 = Math.sqrt(d2),
        b0 = (w1 * w1 - w0 * w0 + rho4 * d2) / (2 * w0 * rho2 * d1),
        b1 = (w1 * w1 - w0 * w0 - rho4 * d2) / (2 * w1 * rho2 * d1),
        r0 = Math.log(Math.sqrt(b0 * b0 + 1) - b0),
        r1 = Math.log(Math.sqrt(b1 * b1 + 1) - b1);
      S = (r1 - r0) / rho;
      i = function i(t) {
        var s = t * S,
          coshr0 = cosh(r0),
          u = w0 / (rho2 * d1) * (coshr0 * tanh(rho * s + r0) - sinh(r0));
        return [ux0 + u * dx, uy0 + u * dy, w0 * coshr0 / cosh(rho * s + r0)];
      };
    }
    i.duration = S * 1e3 * rho / Math.SQRT2;
    return i;
  }
  zoom2.rho = function (_) {
    var _1 = Math.max(1e-3, +_),
      _2 = _1 * _1,
      _4 = _2 * _2;
    return zoomRho(_1, _2, _4);
  };
  return zoom2;
}(Math.SQRT2, 2, 4);
var frame = 0,
  timeout$1 = 0,
  interval = 0,
  pokeDelay = 1e3,
  taskHead,
  taskTail,
  clockLast = 0,
  clockNow = 0,
  clockSkew = 0,
  clock = typeof performance === "object" && performance.now ? performance : Date,
  setFrame = typeof window === "object" && window.requestAnimationFrame ? window.requestAnimationFrame.bind(window) : function (f) {
    setTimeout(f, 17);
  };
function now() {
  return clockNow || (setFrame(clearNow), clockNow = clock.now() + clockSkew);
}
function clearNow() {
  clockNow = 0;
}
function Timer() {
  this._call = this._time = this._next = null;
}
Timer.prototype = timer.prototype = {
  constructor: Timer,
  restart: function restart(callback, delay, time) {
    if (typeof callback !== "function") throw new TypeError("callback is not a function");
    time = (time == null ? now() : +time) + (delay == null ? 0 : +delay);
    if (!this._next && taskTail !== this) {
      if (taskTail) taskTail._next = this;else taskHead = this;
      taskTail = this;
    }
    this._call = callback;
    this._time = time;
    sleep();
  },
  stop: function stop() {
    if (this._call) {
      this._call = null;
      this._time = Infinity;
      sleep();
    }
  }
};
function timer(callback, delay, time) {
  var t = new Timer();
  t.restart(callback, delay, time);
  return t;
}
function timerFlush() {
  now();
  ++frame;
  var t = taskHead,
    e;
  while (t) {
    if ((e = clockNow - t._time) >= 0) t._call.call(void 0, e);
    t = t._next;
  }
  --frame;
}
function wake() {
  clockNow = (clockLast = clock.now()) + clockSkew;
  frame = timeout$1 = 0;
  try {
    timerFlush();
  } finally {
    frame = 0;
    nap();
    clockNow = 0;
  }
}
function poke() {
  var now2 = clock.now(),
    delay = now2 - clockLast;
  if (delay > pokeDelay) clockSkew -= delay, clockLast = now2;
}
function nap() {
  var t0,
    t1 = taskHead,
    t2,
    time = Infinity;
  while (t1) {
    if (t1._call) {
      if (time > t1._time) time = t1._time;
      t0 = t1, t1 = t1._next;
    } else {
      t2 = t1._next, t1._next = null;
      t1 = t0 ? t0._next = t2 : taskHead = t2;
    }
  }
  taskTail = t0;
  sleep(time);
}
function sleep(time) {
  if (frame) return;
  if (timeout$1) timeout$1 = clearTimeout(timeout$1);
  var delay = time - clockNow;
  if (delay > 24) {
    if (time < Infinity) timeout$1 = setTimeout(wake, time - clock.now() - clockSkew);
    if (interval) interval = clearInterval(interval);
  } else {
    if (!interval) clockLast = clock.now(), interval = setInterval(poke, pokeDelay);
    frame = 1, setFrame(wake);
  }
}
function timeout(callback, delay, time) {
  var t = new Timer();
  delay = delay == null ? 0 : +delay;
  t.restart(elapsed => {
    t.stop();
    callback(elapsed + delay);
  }, delay, time);
  return t;
}
var emptyOn = dispatch("start", "end", "cancel", "interrupt");
var emptyTween = [];
var CREATED = 0;
var SCHEDULED = 1;
var STARTING = 2;
var STARTED = 3;
var RUNNING = 4;
var ENDING = 5;
var ENDED = 6;
function schedule(node, name, id2, index, group, timing) {
  var schedules = node.__transition;
  if (!schedules) node.__transition = {};else if (id2 in schedules) return;
  create(node, id2, {
    name,
    index,
    // For context during callback.
    group,
    // For context during callback.
    on: emptyOn,
    tween: emptyTween,
    time: timing.time,
    delay: timing.delay,
    duration: timing.duration,
    ease: timing.ease,
    timer: null,
    state: CREATED
  });
}
function init(node, id2) {
  var schedule2 = get(node, id2);
  if (schedule2.state > CREATED) throw new Error("too late; already scheduled");
  return schedule2;
}
function set(node, id2) {
  var schedule2 = get(node, id2);
  if (schedule2.state > STARTED) throw new Error("too late; already running");
  return schedule2;
}
function get(node, id2) {
  var schedule2 = node.__transition;
  if (!schedule2 || !(schedule2 = schedule2[id2])) throw new Error("transition not found");
  return schedule2;
}
function create(node, id2, self) {
  var schedules = node.__transition,
    tween;
  schedules[id2] = self;
  self.timer = timer(schedule2, 0, self.time);
  function schedule2(elapsed) {
    self.state = SCHEDULED;
    self.timer.restart(start2, self.delay, self.time);
    if (self.delay <= elapsed) start2(elapsed - self.delay);
  }
  function start2(elapsed) {
    var i, j, n, o;
    if (self.state !== SCHEDULED) return stop();
    for (i in schedules) {
      o = schedules[i];
      if (o.name !== self.name) continue;
      if (o.state === STARTED) return timeout(start2);
      if (o.state === RUNNING) {
        o.state = ENDED;
        o.timer.stop();
        o.on.call("interrupt", node, node.__data__, o.index, o.group);
        delete schedules[i];
      } else if (+i < id2) {
        o.state = ENDED;
        o.timer.stop();
        o.on.call("cancel", node, node.__data__, o.index, o.group);
        delete schedules[i];
      }
    }
    timeout(function () {
      if (self.state === STARTED) {
        self.state = RUNNING;
        self.timer.restart(tick, self.delay, self.time);
        tick(elapsed);
      }
    });
    self.state = STARTING;
    self.on.call("start", node, node.__data__, self.index, self.group);
    if (self.state !== STARTING) return;
    self.state = STARTED;
    tween = new Array(n = self.tween.length);
    for (i = 0, j = -1; i < n; ++i) {
      if (o = self.tween[i].value.call(node, node.__data__, self.index, self.group)) {
        tween[++j] = o;
      }
    }
    tween.length = j + 1;
  }
  function tick(elapsed) {
    var t = elapsed < self.duration ? self.ease.call(null, elapsed / self.duration) : (self.timer.restart(stop), self.state = ENDING, 1),
      i = -1,
      n = tween.length;
    while (++i < n) {
      tween[i].call(node, t);
    }
    if (self.state === ENDING) {
      self.on.call("end", node, node.__data__, self.index, self.group);
      stop();
    }
  }
  function stop() {
    self.state = ENDED;
    self.timer.stop();
    delete schedules[id2];
    for (var i in schedules) return;
    delete node.__transition;
  }
}
function interrupt(node, name) {
  var schedules = node.__transition,
    schedule2,
    active,
    empty2 = true,
    i;
  if (!schedules) return;
  name = name == null ? null : name + "";
  for (i in schedules) {
    if ((schedule2 = schedules[i]).name !== name) {
      empty2 = false;
      continue;
    }
    active = schedule2.state > STARTING && schedule2.state < ENDING;
    schedule2.state = ENDED;
    schedule2.timer.stop();
    schedule2.on.call(active ? "interrupt" : "cancel", node, node.__data__, schedule2.index, schedule2.group);
    delete schedules[i];
  }
  if (empty2) delete node.__transition;
}
function selection_interrupt(name) {
  return this.each(function () {
    interrupt(this, name);
  });
}
function tweenRemove(id2, name) {
  var tween0, tween1;
  return function () {
    var schedule2 = set(this, id2),
      tween = schedule2.tween;
    if (tween !== tween0) {
      tween1 = tween0 = tween;
      for (var i = 0, n = tween1.length; i < n; ++i) {
        if (tween1[i].name === name) {
          tween1 = tween1.slice();
          tween1.splice(i, 1);
          break;
        }
      }
    }
    schedule2.tween = tween1;
  };
}
function tweenFunction(id2, name, value) {
  var tween0, tween1;
  if (typeof value !== "function") throw new Error();
  return function () {
    var schedule2 = set(this, id2),
      tween = schedule2.tween;
    if (tween !== tween0) {
      tween1 = (tween0 = tween).slice();
      for (var t = {
          name,
          value
        }, i = 0, n = tween1.length; i < n; ++i) {
        if (tween1[i].name === name) {
          tween1[i] = t;
          break;
        }
      }
      if (i === n) tween1.push(t);
    }
    schedule2.tween = tween1;
  };
}
function transition_tween(name, value) {
  var id2 = this._id;
  name += "";
  if (arguments.length < 2) {
    var tween = get(this.node(), id2).tween;
    for (var i = 0, n = tween.length, t; i < n; ++i) {
      if ((t = tween[i]).name === name) {
        return t.value;
      }
    }
    return null;
  }
  return this.each((value == null ? tweenRemove : tweenFunction)(id2, name, value));
}
function tweenValue(transition, name, value) {
  var id2 = transition._id;
  transition.each(function () {
    var schedule2 = set(this, id2);
    (schedule2.value || (schedule2.value = {}))[name] = value.apply(this, arguments);
  });
  return function (node) {
    return get(node, id2).value[name];
  };
}
function interpolate(a, b) {
  var c;
  return (typeof b === "number" ? interpolateNumber : b instanceof color ? interpolateRgb : (c = color(b)) ? (b = c, interpolateRgb) : interpolateString)(a, b);
}
function attrRemove(name) {
  return function () {
    this.removeAttribute(name);
  };
}
function attrRemoveNS(fullname) {
  return function () {
    this.removeAttributeNS(fullname.space, fullname.local);
  };
}
function attrConstant(name, interpolate2, value1) {
  var string00,
    string1 = value1 + "",
    interpolate0;
  return function () {
    var string0 = this.getAttribute(name);
    return string0 === string1 ? null : string0 === string00 ? interpolate0 : interpolate0 = interpolate2(string00 = string0, value1);
  };
}
function attrConstantNS(fullname, interpolate2, value1) {
  var string00,
    string1 = value1 + "",
    interpolate0;
  return function () {
    var string0 = this.getAttributeNS(fullname.space, fullname.local);
    return string0 === string1 ? null : string0 === string00 ? interpolate0 : interpolate0 = interpolate2(string00 = string0, value1);
  };
}
function attrFunction(name, interpolate2, value) {
  var string00, string10, interpolate0;
  return function () {
    var string0,
      value1 = value(this),
      string1;
    if (value1 == null) return void this.removeAttribute(name);
    string0 = this.getAttribute(name);
    string1 = value1 + "";
    return string0 === string1 ? null : string0 === string00 && string1 === string10 ? interpolate0 : (string10 = string1, interpolate0 = interpolate2(string00 = string0, value1));
  };
}
function attrFunctionNS(fullname, interpolate2, value) {
  var string00, string10, interpolate0;
  return function () {
    var string0,
      value1 = value(this),
      string1;
    if (value1 == null) return void this.removeAttributeNS(fullname.space, fullname.local);
    string0 = this.getAttributeNS(fullname.space, fullname.local);
    string1 = value1 + "";
    return string0 === string1 ? null : string0 === string00 && string1 === string10 ? interpolate0 : (string10 = string1, interpolate0 = interpolate2(string00 = string0, value1));
  };
}
function transition_attr(name, value) {
  var fullname = namespace(name),
    i = fullname === "transform" ? interpolateTransformSvg : interpolate;
  return this.attrTween(name, typeof value === "function" ? (fullname.local ? attrFunctionNS : attrFunction)(fullname, i, tweenValue(this, "attr." + name, value)) : value == null ? (fullname.local ? attrRemoveNS : attrRemove)(fullname) : (fullname.local ? attrConstantNS : attrConstant)(fullname, i, value));
}
function attrInterpolate(name, i) {
  return function (t) {
    this.setAttribute(name, i.call(this, t));
  };
}
function attrInterpolateNS(fullname, i) {
  return function (t) {
    this.setAttributeNS(fullname.space, fullname.local, i.call(this, t));
  };
}
function attrTweenNS(fullname, value) {
  var t0, i0;
  function tween() {
    var i = value.apply(this, arguments);
    if (i !== i0) t0 = (i0 = i) && attrInterpolateNS(fullname, i);
    return t0;
  }
  tween._value = value;
  return tween;
}
function attrTween(name, value) {
  var t0, i0;
  function tween() {
    var i = value.apply(this, arguments);
    if (i !== i0) t0 = (i0 = i) && attrInterpolate(name, i);
    return t0;
  }
  tween._value = value;
  return tween;
}
function transition_attrTween(name, value) {
  var key = "attr." + name;
  if (arguments.length < 2) return (key = this.tween(key)) && key._value;
  if (value == null) return this.tween(key, null);
  if (typeof value !== "function") throw new Error();
  var fullname = namespace(name);
  return this.tween(key, (fullname.local ? attrTweenNS : attrTween)(fullname, value));
}
function delayFunction(id2, value) {
  return function () {
    init(this, id2).delay = +value.apply(this, arguments);
  };
}
function delayConstant(id2, value) {
  return value = +value, function () {
    init(this, id2).delay = value;
  };
}
function transition_delay(value) {
  var id2 = this._id;
  return arguments.length ? this.each((typeof value === "function" ? delayFunction : delayConstant)(id2, value)) : get(this.node(), id2).delay;
}
function durationFunction(id2, value) {
  return function () {
    set(this, id2).duration = +value.apply(this, arguments);
  };
}
function durationConstant(id2, value) {
  return value = +value, function () {
    set(this, id2).duration = value;
  };
}
function transition_duration(value) {
  var id2 = this._id;
  return arguments.length ? this.each((typeof value === "function" ? durationFunction : durationConstant)(id2, value)) : get(this.node(), id2).duration;
}
function easeConstant(id2, value) {
  if (typeof value !== "function") throw new Error();
  return function () {
    set(this, id2).ease = value;
  };
}
function transition_ease(value) {
  var id2 = this._id;
  return arguments.length ? this.each(easeConstant(id2, value)) : get(this.node(), id2).ease;
}
function easeVarying(id2, value) {
  return function () {
    var v = value.apply(this, arguments);
    if (typeof v !== "function") throw new Error();
    set(this, id2).ease = v;
  };
}
function transition_easeVarying(value) {
  if (typeof value !== "function") throw new Error();
  return this.each(easeVarying(this._id, value));
}
function transition_filter(match) {
  if (typeof match !== "function") match = matcher(match);
  for (var groups = this._groups, m = groups.length, subgroups = new Array(m), j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, subgroup = subgroups[j] = [], node, i = 0; i < n; ++i) {
      if ((node = group[i]) && match.call(node, node.__data__, i, group)) {
        subgroup.push(node);
      }
    }
  }
  return new Transition(subgroups, this._parents, this._name, this._id);
}
function transition_merge(transition) {
  if (transition._id !== this._id) throw new Error();
  for (var groups0 = this._groups, groups1 = transition._groups, m0 = groups0.length, m1 = groups1.length, m = Math.min(m0, m1), merges = new Array(m0), j = 0; j < m; ++j) {
    for (var group0 = groups0[j], group1 = groups1[j], n = group0.length, merge = merges[j] = new Array(n), node, i = 0; i < n; ++i) {
      if (node = group0[i] || group1[i]) {
        merge[i] = node;
      }
    }
  }
  for (; j < m0; ++j) {
    merges[j] = groups0[j];
  }
  return new Transition(merges, this._parents, this._name, this._id);
}
function start(name) {
  return (name + "").trim().split(/^|\s+/).every(function (t) {
    var i = t.indexOf(".");
    if (i >= 0) t = t.slice(0, i);
    return !t || t === "start";
  });
}
function onFunction(id2, name, listener) {
  var on0,
    on1,
    sit = start(name) ? init : set;
  return function () {
    var schedule2 = sit(this, id2),
      on = schedule2.on;
    if (on !== on0) (on1 = (on0 = on).copy()).on(name, listener);
    schedule2.on = on1;
  };
}
function transition_on(name, listener) {
  var id2 = this._id;
  return arguments.length < 2 ? get(this.node(), id2).on.on(name) : this.each(onFunction(id2, name, listener));
}
function removeFunction(id2) {
  return function () {
    var parent = this.parentNode;
    for (var i in this.__transition) if (+i !== id2) return;
    if (parent) parent.removeChild(this);
  };
}
function transition_remove() {
  return this.on("end.remove", removeFunction(this._id));
}
function transition_select(select2) {
  var name = this._name,
    id2 = this._id;
  if (typeof select2 !== "function") select2 = selector(select2);
  for (var groups = this._groups, m = groups.length, subgroups = new Array(m), j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, subgroup = subgroups[j] = new Array(n), node, subnode, i = 0; i < n; ++i) {
      if ((node = group[i]) && (subnode = select2.call(node, node.__data__, i, group))) {
        if ("__data__" in node) subnode.__data__ = node.__data__;
        subgroup[i] = subnode;
        schedule(subgroup[i], name, id2, i, subgroup, get(node, id2));
      }
    }
  }
  return new Transition(subgroups, this._parents, name, id2);
}
function transition_selectAll(select2) {
  var name = this._name,
    id2 = this._id;
  if (typeof select2 !== "function") select2 = selectorAll(select2);
  for (var groups = this._groups, m = groups.length, subgroups = [], parents = [], j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, node, i = 0; i < n; ++i) {
      if (node = group[i]) {
        for (var children2 = select2.call(node, node.__data__, i, group), child, inherit2 = get(node, id2), k = 0, l = children2.length; k < l; ++k) {
          if (child = children2[k]) {
            schedule(child, name, id2, k, children2, inherit2);
          }
        }
        subgroups.push(children2);
        parents.push(node);
      }
    }
  }
  return new Transition(subgroups, parents, name, id2);
}
var Selection = selection.prototype.constructor;
function transition_selection() {
  return new Selection(this._groups, this._parents);
}
function styleNull(name, interpolate2) {
  var string00, string10, interpolate0;
  return function () {
    var string0 = styleValue(this, name),
      string1 = (this.style.removeProperty(name), styleValue(this, name));
    return string0 === string1 ? null : string0 === string00 && string1 === string10 ? interpolate0 : interpolate0 = interpolate2(string00 = string0, string10 = string1);
  };
}
function styleRemove(name) {
  return function () {
    this.style.removeProperty(name);
  };
}
function styleConstant(name, interpolate2, value1) {
  var string00,
    string1 = value1 + "",
    interpolate0;
  return function () {
    var string0 = styleValue(this, name);
    return string0 === string1 ? null : string0 === string00 ? interpolate0 : interpolate0 = interpolate2(string00 = string0, value1);
  };
}
function styleFunction(name, interpolate2, value) {
  var string00, string10, interpolate0;
  return function () {
    var string0 = styleValue(this, name),
      value1 = value(this),
      string1 = value1 + "";
    if (value1 == null) string1 = value1 = (this.style.removeProperty(name), styleValue(this, name));
    return string0 === string1 ? null : string0 === string00 && string1 === string10 ? interpolate0 : (string10 = string1, interpolate0 = interpolate2(string00 = string0, value1));
  };
}
function styleMaybeRemove(id2, name) {
  var on0,
    on1,
    listener0,
    key = "style." + name,
    event = "end." + key,
    remove2;
  return function () {
    var schedule2 = set(this, id2),
      on = schedule2.on,
      listener = schedule2.value[key] == null ? remove2 || (remove2 = styleRemove(name)) : void 0;
    if (on !== on0 || listener0 !== listener) (on1 = (on0 = on).copy()).on(event, listener0 = listener);
    schedule2.on = on1;
  };
}
function transition_style(name, value, priority) {
  var i = (name += "") === "transform" ? interpolateTransformCss : interpolate;
  return value == null ? this.styleTween(name, styleNull(name, i)).on("end.style." + name, styleRemove(name)) : typeof value === "function" ? this.styleTween(name, styleFunction(name, i, tweenValue(this, "style." + name, value))).each(styleMaybeRemove(this._id, name)) : this.styleTween(name, styleConstant(name, i, value), priority).on("end.style." + name, null);
}
function styleInterpolate(name, i, priority) {
  return function (t) {
    this.style.setProperty(name, i.call(this, t), priority);
  };
}
function styleTween(name, value, priority) {
  var t, i0;
  function tween() {
    var i = value.apply(this, arguments);
    if (i !== i0) t = (i0 = i) && styleInterpolate(name, i, priority);
    return t;
  }
  tween._value = value;
  return tween;
}
function transition_styleTween(name, value, priority) {
  var key = "style." + (name += "");
  if (arguments.length < 2) return (key = this.tween(key)) && key._value;
  if (value == null) return this.tween(key, null);
  if (typeof value !== "function") throw new Error();
  return this.tween(key, styleTween(name, value, priority == null ? "" : priority));
}
function textConstant(value) {
  return function () {
    this.textContent = value;
  };
}
function textFunction(value) {
  return function () {
    var value1 = value(this);
    this.textContent = value1 == null ? "" : value1;
  };
}
function transition_text(value) {
  return this.tween("text", typeof value === "function" ? textFunction(tweenValue(this, "text", value)) : textConstant(value == null ? "" : value + ""));
}
function textInterpolate(i) {
  return function (t) {
    this.textContent = i.call(this, t);
  };
}
function textTween(value) {
  var t0, i0;
  function tween() {
    var i = value.apply(this, arguments);
    if (i !== i0) t0 = (i0 = i) && textInterpolate(i);
    return t0;
  }
  tween._value = value;
  return tween;
}
function transition_textTween(value) {
  var key = "text";
  if (arguments.length < 1) return (key = this.tween(key)) && key._value;
  if (value == null) return this.tween(key, null);
  if (typeof value !== "function") throw new Error();
  return this.tween(key, textTween(value));
}
function transition_transition() {
  var name = this._name,
    id0 = this._id,
    id1 = newId();
  for (var groups = this._groups, m = groups.length, j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, node, i = 0; i < n; ++i) {
      if (node = group[i]) {
        var inherit2 = get(node, id0);
        schedule(node, name, id1, i, group, {
          time: inherit2.time + inherit2.delay + inherit2.duration,
          delay: 0,
          duration: inherit2.duration,
          ease: inherit2.ease
        });
      }
    }
  }
  return new Transition(groups, this._parents, name, id1);
}
function transition_end() {
  var on0,
    on1,
    that = this,
    id2 = that._id,
    size = that.size();
  return new Promise(function (resolve, reject) {
    var cancel = {
        value: reject
      },
      end = {
        value: function value() {
          if (--size === 0) resolve();
        }
      };
    that.each(function () {
      var schedule2 = set(this, id2),
        on = schedule2.on;
      if (on !== on0) {
        on1 = (on0 = on).copy();
        on1._.cancel.push(cancel);
        on1._.interrupt.push(cancel);
        on1._.end.push(end);
      }
      schedule2.on = on1;
    });
    if (size === 0) resolve();
  });
}
var id = 0;
function Transition(groups, parents, name, id2) {
  this._groups = groups;
  this._parents = parents;
  this._name = name;
  this._id = id2;
}
function newId() {
  return ++id;
}
var selection_prototype = selection.prototype;
Transition.prototype = {
  constructor: Transition,
  select: transition_select,
  selectAll: transition_selectAll,
  selectChild: selection_prototype.selectChild,
  selectChildren: selection_prototype.selectChildren,
  filter: transition_filter,
  merge: transition_merge,
  selection: transition_selection,
  transition: transition_transition,
  call: selection_prototype.call,
  nodes: selection_prototype.nodes,
  node: selection_prototype.node,
  size: selection_prototype.size,
  empty: selection_prototype.empty,
  each: selection_prototype.each,
  on: transition_on,
  attr: transition_attr,
  attrTween: transition_attrTween,
  style: transition_style,
  styleTween: transition_styleTween,
  text: transition_text,
  textTween: transition_textTween,
  remove: transition_remove,
  tween: transition_tween,
  delay: transition_delay,
  duration: transition_duration,
  ease: transition_ease,
  easeVarying: transition_easeVarying,
  end: transition_end,
  [Symbol.iterator]: selection_prototype[Symbol.iterator]
};
function cubicInOut(t) {
  return ((t *= 2) <= 1 ? t * t * t : (t -= 2) * t * t + 2) / 2;
}
var defaultTiming = {
  time: null,
  // Set on use.
  delay: 0,
  duration: 250,
  ease: cubicInOut
};
function inherit(node, id2) {
  var timing;
  while (!(timing = node.__transition) || !(timing = timing[id2])) {
    if (!(node = node.parentNode)) {
      throw new Error("transition " + id2 + " not found");
    }
  }
  return timing;
}
function selection_transition(name) {
  var id2, timing;
  if (name instanceof Transition) {
    id2 = name._id, name = name._name;
  } else {
    id2 = newId(), (timing = defaultTiming).time = now(), name = name == null ? null : name + "";
  }
  for (var groups = this._groups, m = groups.length, j = 0; j < m; ++j) {
    for (var group = groups[j], n = group.length, node, i = 0; i < n; ++i) {
      if (node = group[i]) {
        schedule(node, name, id2, i, group, timing || inherit(node, id2));
      }
    }
  }
  return new Transition(groups, this._parents, name, id2);
}
selection.prototype.interrupt = selection_interrupt;
selection.prototype.transition = selection_transition;
const constant = x => () => x;
function ZoomEvent(type, _ref) {
  let sourceEvent2 = _ref.sourceEvent,
    target = _ref.target,
    transform = _ref.transform,
    dispatch2 = _ref.dispatch;
  Object.defineProperties(this, {
    type: {
      value: type,
      enumerable: true,
      configurable: true
    },
    sourceEvent: {
      value: sourceEvent2,
      enumerable: true,
      configurable: true
    },
    target: {
      value: target,
      enumerable: true,
      configurable: true
    },
    transform: {
      value: transform,
      enumerable: true,
      configurable: true
    },
    _: {
      value: dispatch2
    }
  });
}
function Transform(k, x, y) {
  this.k = k;
  this.x = x;
  this.y = y;
}
Transform.prototype = {
  constructor: Transform,
  scale: function scale(k) {
    return k === 1 ? this : new Transform(this.k * k, this.x, this.y);
  },
  translate: function translate(x, y) {
    return x === 0 & y === 0 ? this : new Transform(this.k, this.x + this.k * x, this.y + this.k * y);
  },
  apply: function apply(point) {
    return [point[0] * this.k + this.x, point[1] * this.k + this.y];
  },
  applyX: function applyX(x) {
    return x * this.k + this.x;
  },
  applyY: function applyY(y) {
    return y * this.k + this.y;
  },
  invert: function invert(location) {
    return [(location[0] - this.x) / this.k, (location[1] - this.y) / this.k];
  },
  invertX: function invertX(x) {
    return (x - this.x) / this.k;
  },
  invertY: function invertY(y) {
    return (y - this.y) / this.k;
  },
  rescaleX: function rescaleX(x) {
    return x.copy().domain(x.range().map(this.invertX, this).map(x.invert, x));
  },
  rescaleY: function rescaleY(y) {
    return y.copy().domain(y.range().map(this.invertY, this).map(y.invert, y));
  },
  toString: function toString() {
    return "translate(" + this.x + "," + this.y + ") scale(" + this.k + ")";
  }
};
var identity = new Transform(1, 0, 0);
Transform.prototype;
function nopropagation(event) {
  event.stopImmediatePropagation();
}
function noevent(event) {
  event.preventDefault();
  event.stopImmediatePropagation();
}
function defaultFilter(event) {
  return (!event.ctrlKey || event.type === "wheel") && !event.button;
}
function defaultExtent() {
  var e = this;
  if (e instanceof SVGElement) {
    e = e.ownerSVGElement || e;
    if (e.hasAttribute("viewBox")) {
      e = e.viewBox.baseVal;
      return [[e.x, e.y], [e.x + e.width, e.y + e.height]];
    }
    return [[0, 0], [e.width.baseVal.value, e.height.baseVal.value]];
  }
  return [[0, 0], [e.clientWidth, e.clientHeight]];
}
function defaultTransform() {
  return this.__zoom || identity;
}
function defaultWheelDelta(event) {
  return -event.deltaY * (event.deltaMode === 1 ? 0.05 : event.deltaMode ? 1 : 2e-3) * (event.ctrlKey ? 10 : 1);
}
function defaultTouchable() {
  return navigator.maxTouchPoints || "ontouchstart" in this;
}
function defaultConstrain(transform, extent, translateExtent) {
  var dx0 = transform.invertX(extent[0][0]) - translateExtent[0][0],
    dx1 = transform.invertX(extent[1][0]) - translateExtent[1][0],
    dy0 = transform.invertY(extent[0][1]) - translateExtent[0][1],
    dy1 = transform.invertY(extent[1][1]) - translateExtent[1][1];
  return transform.translate(dx1 > dx0 ? (dx0 + dx1) / 2 : Math.min(0, dx0) || Math.max(0, dx1), dy1 > dy0 ? (dy0 + dy1) / 2 : Math.min(0, dy0) || Math.max(0, dy1));
}
function zoom() {
  var filter2 = defaultFilter,
    extent = defaultExtent,
    constrain = defaultConstrain,
    wheelDelta2 = defaultWheelDelta,
    touchable = defaultTouchable,
    scaleExtent = [0, Infinity],
    translateExtent = [[-Infinity, -Infinity], [Infinity, Infinity]],
    duration = 250,
    interpolate2 = interpolateZoom,
    listeners = dispatch("start", "zoom", "end"),
    touchstarting,
    touchfirst,
    touchending,
    touchDelay = 500,
    wheelDelay = 150,
    clickDistance2 = 0,
    tapDistance = 10;
  function zoom2(selection2) {
    selection2.property("__zoom", defaultTransform).on("wheel.zoom", wheeled, {
      passive: false
    }).on("mousedown.zoom", mousedowned).on("dblclick.zoom", dblclicked).filter(touchable).on("touchstart.zoom", touchstarted).on("touchmove.zoom", touchmoved).on("touchend.zoom touchcancel.zoom", touchended).style("-webkit-tap-highlight-color", "rgba(0,0,0,0)");
  }
  zoom2.transform = function (collection, transform, point, event) {
    var selection2 = collection.selection ? collection.selection() : collection;
    selection2.property("__zoom", defaultTransform);
    if (collection !== selection2) {
      schedule2(collection, transform, point, event);
    } else {
      selection2.interrupt().each(function () {
        gesture(this, arguments).event(event).start().zoom(null, typeof transform === "function" ? transform.apply(this, arguments) : transform).end();
      });
    }
  };
  zoom2.scaleBy = function (selection2, k, p, event) {
    zoom2.scaleTo(selection2, function () {
      var k0 = this.__zoom.k,
        k1 = typeof k === "function" ? k.apply(this, arguments) : k;
      return k0 * k1;
    }, p, event);
  };
  zoom2.scaleTo = function (selection2, k, p, event) {
    zoom2.transform(selection2, function () {
      var e = extent.apply(this, arguments),
        t0 = this.__zoom,
        p0 = p == null ? centroid(e) : typeof p === "function" ? p.apply(this, arguments) : p,
        p1 = t0.invert(p0),
        k1 = typeof k === "function" ? k.apply(this, arguments) : k;
      return constrain(translate(scale(t0, k1), p0, p1), e, translateExtent);
    }, p, event);
  };
  zoom2.translateBy = function (selection2, x, y, event) {
    zoom2.transform(selection2, function () {
      return constrain(this.__zoom.translate(typeof x === "function" ? x.apply(this, arguments) : x, typeof y === "function" ? y.apply(this, arguments) : y), extent.apply(this, arguments), translateExtent);
    }, null, event);
  };
  zoom2.translateTo = function (selection2, x, y, p, event) {
    zoom2.transform(selection2, function () {
      var e = extent.apply(this, arguments),
        t = this.__zoom,
        p0 = p == null ? centroid(e) : typeof p === "function" ? p.apply(this, arguments) : p;
      return constrain(identity.translate(p0[0], p0[1]).scale(t.k).translate(typeof x === "function" ? -x.apply(this, arguments) : -x, typeof y === "function" ? -y.apply(this, arguments) : -y), e, translateExtent);
    }, p, event);
  };
  function scale(transform, k) {
    k = Math.max(scaleExtent[0], Math.min(scaleExtent[1], k));
    return k === transform.k ? transform : new Transform(k, transform.x, transform.y);
  }
  function translate(transform, p0, p1) {
    var x = p0[0] - p1[0] * transform.k,
      y = p0[1] - p1[1] * transform.k;
    return x === transform.x && y === transform.y ? transform : new Transform(transform.k, x, y);
  }
  function centroid(extent2) {
    return [(+extent2[0][0] + +extent2[1][0]) / 2, (+extent2[0][1] + +extent2[1][1]) / 2];
  }
  function schedule2(transition, transform, point, event) {
    transition.on("start.zoom", function () {
      gesture(this, arguments).event(event).start();
    }).on("interrupt.zoom end.zoom", function () {
      gesture(this, arguments).event(event).end();
    }).tween("zoom", function () {
      var that = this,
        args = arguments,
        g = gesture(that, args).event(event),
        e = extent.apply(that, args),
        p = point == null ? centroid(e) : typeof point === "function" ? point.apply(that, args) : point,
        w = Math.max(e[1][0] - e[0][0], e[1][1] - e[0][1]),
        a = that.__zoom,
        b = typeof transform === "function" ? transform.apply(that, args) : transform,
        i = interpolate2(a.invert(p).concat(w / a.k), b.invert(p).concat(w / b.k));
      return function (t) {
        if (t === 1) t = b;else {
          var l = i(t),
            k = w / l[2];
          t = new Transform(k, p[0] - l[0] * k, p[1] - l[1] * k);
        }
        g.zoom(null, t);
      };
    });
  }
  function gesture(that, args, clean) {
    return !clean && that.__zooming || new Gesture(that, args);
  }
  function Gesture(that, args) {
    this.that = that;
    this.args = args;
    this.active = 0;
    this.sourceEvent = null;
    this.extent = extent.apply(that, args);
    this.taps = 0;
  }
  Gesture.prototype = {
    event: function event(_event) {
      if (_event) this.sourceEvent = _event;
      return this;
    },
    start: function start() {
      if (++this.active === 1) {
        this.that.__zooming = this;
        this.emit("start");
      }
      return this;
    },
    zoom: function zoom(key, transform) {
      if (this.mouse && key !== "mouse") this.mouse[1] = transform.invert(this.mouse[0]);
      if (this.touch0 && key !== "touch") this.touch0[1] = transform.invert(this.touch0[0]);
      if (this.touch1 && key !== "touch") this.touch1[1] = transform.invert(this.touch1[0]);
      this.that.__zoom = transform;
      this.emit("zoom");
      return this;
    },
    end: function end() {
      if (--this.active === 0) {
        delete this.that.__zooming;
        this.emit("end");
      }
      return this;
    },
    emit: function emit(type) {
      var d = select(this.that).datum();
      listeners.call(type, this.that, new ZoomEvent(type, {
        sourceEvent: this.sourceEvent,
        target: zoom2,
        transform: this.that.__zoom,
        dispatch: listeners
      }), d);
    }
  };
  function wheeled(event) {
    for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
      args[_key - 1] = arguments[_key];
    }
    if (!filter2.apply(this, arguments)) return;
    var g = gesture(this, args).event(event),
      t = this.__zoom,
      k = Math.max(scaleExtent[0], Math.min(scaleExtent[1], t.k * Math.pow(2, wheelDelta2.apply(this, arguments)))),
      p = pointer(event);
    if (g.wheel) {
      if (g.mouse[0][0] !== p[0] || g.mouse[0][1] !== p[1]) {
        g.mouse[1] = t.invert(g.mouse[0] = p);
      }
      clearTimeout(g.wheel);
    } else if (t.k === k) return;else {
      g.mouse = [p, t.invert(p)];
      interrupt(this);
      g.start();
    }
    noevent(event);
    g.wheel = setTimeout(wheelidled, wheelDelay);
    g.zoom("mouse", constrain(translate(scale(t, k), g.mouse[0], g.mouse[1]), g.extent, translateExtent));
    function wheelidled() {
      g.wheel = null;
      g.end();
    }
  }
  function mousedowned(event) {
    for (var _len2 = arguments.length, args = new Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
      args[_key2 - 1] = arguments[_key2];
    }
    if (touchending || !filter2.apply(this, arguments)) return;
    var currentTarget = event.currentTarget,
      g = gesture(this, args, true).event(event),
      v = select(event.view).on("mousemove.zoom", mousemoved, true).on("mouseup.zoom", mouseupped, true),
      p = pointer(event, currentTarget),
      x0 = event.clientX,
      y0 = event.clientY;
    dragDisable(event.view);
    nopropagation(event);
    g.mouse = [p, this.__zoom.invert(p)];
    interrupt(this);
    g.start();
    function mousemoved(event2) {
      noevent(event2);
      if (!g.moved) {
        var dx = event2.clientX - x0,
          dy = event2.clientY - y0;
        g.moved = dx * dx + dy * dy > clickDistance2;
      }
      g.event(event2).zoom("mouse", constrain(translate(g.that.__zoom, g.mouse[0] = pointer(event2, currentTarget), g.mouse[1]), g.extent, translateExtent));
    }
    function mouseupped(event2) {
      v.on("mousemove.zoom mouseup.zoom", null);
      yesdrag(event2.view, g.moved);
      noevent(event2);
      g.event(event2).end();
    }
  }
  function dblclicked(event) {
    for (var _len3 = arguments.length, args = new Array(_len3 > 1 ? _len3 - 1 : 0), _key3 = 1; _key3 < _len3; _key3++) {
      args[_key3 - 1] = arguments[_key3];
    }
    if (!filter2.apply(this, arguments)) return;
    var t0 = this.__zoom,
      p0 = pointer(event.changedTouches ? event.changedTouches[0] : event, this),
      p1 = t0.invert(p0),
      k1 = t0.k * (event.shiftKey ? 0.5 : 2),
      t1 = constrain(translate(scale(t0, k1), p0, p1), extent.apply(this, args), translateExtent);
    noevent(event);
    if (duration > 0) select(this).transition().duration(duration).call(schedule2, t1, p0, event);else select(this).call(zoom2.transform, t1, p0, event);
  }
  function touchstarted(event) {
    for (var _len4 = arguments.length, args = new Array(_len4 > 1 ? _len4 - 1 : 0), _key4 = 1; _key4 < _len4; _key4++) {
      args[_key4 - 1] = arguments[_key4];
    }
    if (!filter2.apply(this, arguments)) return;
    var touches = event.touches,
      n = touches.length,
      g = gesture(this, args, event.changedTouches.length === n).event(event),
      started,
      i,
      t,
      p;
    nopropagation(event);
    for (i = 0; i < n; ++i) {
      t = touches[i], p = pointer(t, this);
      p = [p, this.__zoom.invert(p), t.identifier];
      if (!g.touch0) g.touch0 = p, started = true, g.taps = 1 + !!touchstarting;else if (!g.touch1 && g.touch0[2] !== p[2]) g.touch1 = p, g.taps = 0;
    }
    if (touchstarting) touchstarting = clearTimeout(touchstarting);
    if (started) {
      if (g.taps < 2) touchfirst = p[0], touchstarting = setTimeout(function () {
        touchstarting = null;
      }, touchDelay);
      interrupt(this);
      g.start();
    }
  }
  function touchmoved(event) {
    if (!this.__zooming) return;
    for (var _len5 = arguments.length, args = new Array(_len5 > 1 ? _len5 - 1 : 0), _key5 = 1; _key5 < _len5; _key5++) {
      args[_key5 - 1] = arguments[_key5];
    }
    var g = gesture(this, args).event(event),
      touches = event.changedTouches,
      n = touches.length,
      i,
      t,
      p,
      l;
    noevent(event);
    for (i = 0; i < n; ++i) {
      t = touches[i], p = pointer(t, this);
      if (g.touch0 && g.touch0[2] === t.identifier) g.touch0[0] = p;else if (g.touch1 && g.touch1[2] === t.identifier) g.touch1[0] = p;
    }
    t = g.that.__zoom;
    if (g.touch1) {
      var p0 = g.touch0[0],
        l0 = g.touch0[1],
        p1 = g.touch1[0],
        l1 = g.touch1[1],
        dp = (dp = p1[0] - p0[0]) * dp + (dp = p1[1] - p0[1]) * dp,
        dl = (dl = l1[0] - l0[0]) * dl + (dl = l1[1] - l0[1]) * dl;
      t = scale(t, Math.sqrt(dp / dl));
      p = [(p0[0] + p1[0]) / 2, (p0[1] + p1[1]) / 2];
      l = [(l0[0] + l1[0]) / 2, (l0[1] + l1[1]) / 2];
    } else if (g.touch0) p = g.touch0[0], l = g.touch0[1];else return;
    g.zoom("touch", constrain(translate(t, p, l), g.extent, translateExtent));
  }
  function touchended(event) {
    for (var _len6 = arguments.length, args = new Array(_len6 > 1 ? _len6 - 1 : 0), _key6 = 1; _key6 < _len6; _key6++) {
      args[_key6 - 1] = arguments[_key6];
    }
    if (!this.__zooming) return;
    var g = gesture(this, args).event(event),
      touches = event.changedTouches,
      n = touches.length,
      i,
      t;
    nopropagation(event);
    if (touchending) clearTimeout(touchending);
    touchending = setTimeout(function () {
      touchending = null;
    }, touchDelay);
    for (i = 0; i < n; ++i) {
      t = touches[i];
      if (g.touch0 && g.touch0[2] === t.identifier) delete g.touch0;else if (g.touch1 && g.touch1[2] === t.identifier) delete g.touch1;
    }
    if (g.touch1 && !g.touch0) g.touch0 = g.touch1, delete g.touch1;
    if (g.touch0) g.touch0[1] = this.__zoom.invert(g.touch0[0]);else {
      g.end();
      if (g.taps === 2) {
        t = pointer(t, this);
        if (Math.hypot(touchfirst[0] - t[0], touchfirst[1] - t[1]) < tapDistance) {
          var p = select(this).on("dblclick.zoom");
          if (p) p.apply(this, arguments);
        }
      }
    }
  }
  zoom2.wheelDelta = function (_) {
    return arguments.length ? (wheelDelta2 = typeof _ === "function" ? _ : constant(+_), zoom2) : wheelDelta2;
  };
  zoom2.filter = function (_) {
    return arguments.length ? (filter2 = typeof _ === "function" ? _ : constant(!!_), zoom2) : filter2;
  };
  zoom2.touchable = function (_) {
    return arguments.length ? (touchable = typeof _ === "function" ? _ : constant(!!_), zoom2) : touchable;
  };
  zoom2.extent = function (_) {
    return arguments.length ? (extent = typeof _ === "function" ? _ : constant([[+_[0][0], +_[0][1]], [+_[1][0], +_[1][1]]]), zoom2) : extent;
  };
  zoom2.scaleExtent = function (_) {
    return arguments.length ? (scaleExtent[0] = +_[0], scaleExtent[1] = +_[1], zoom2) : [scaleExtent[0], scaleExtent[1]];
  };
  zoom2.translateExtent = function (_) {
    return arguments.length ? (translateExtent[0][0] = +_[0][0], translateExtent[1][0] = +_[1][0], translateExtent[0][1] = +_[0][1], translateExtent[1][1] = +_[1][1], zoom2) : [[translateExtent[0][0], translateExtent[0][1]], [translateExtent[1][0], translateExtent[1][1]]];
  };
  zoom2.constrain = function (_) {
    return arguments.length ? (constrain = _, zoom2) : constrain;
  };
  zoom2.duration = function (_) {
    return arguments.length ? (duration = +_, zoom2) : duration;
  };
  zoom2.interpolate = function (_) {
    return arguments.length ? (interpolate2 = _, zoom2) : interpolate2;
  };
  zoom2.on = function () {
    var value = listeners.on.apply(listeners, arguments);
    return value === listeners ? zoom2 : value;
  };
  zoom2.clickDistance = function (_) {
    return arguments.length ? (clickDistance2 = (_ = +_) * _, zoom2) : Math.sqrt(clickDistance2);
  };
  zoom2.tapDistance = function (_) {
    return arguments.length ? (tapDistance = +_, zoom2) : tapDistance;
  };
  return zoom2;
}
const Slots = Symbol("MiniMapSlots");
const _hoisted_1$1$1 = ["id", "x", "y", "rx", "ry", "width", "height", "fill", "stroke", "stroke-width", "shape-rendering"];
const __default__$1 = {
  name: "MiniMapNode",
  compatConfig: {
    MODE: 3
  },
  inheritAttrs: false
};
const _sfc_main$1 = /* @__PURE__ */defineComponent(_extends({}, __default__$1, {
  props: {
    id: {},
    type: {},
    selected: {
      type: Boolean
    },
    dragging: {
      type: Boolean
    },
    position: {},
    dimensions: {},
    borderRadius: {},
    color: {},
    shapeRendering: {},
    strokeColor: {},
    strokeWidth: {},
    hidden: {
      type: Boolean
    }
  },
  emits: ["click", "dblclick", "mouseenter", "mousemove", "mouseleave"],
  setup(__props, _ref2) {
    let emits = _ref2.emit;
    const props = __props;
    const miniMapSlots = inject(Slots);
    const attrs = useAttrs();
    const style = toRef(() => {
      var _attrs$style;
      return (_attrs$style = attrs.style) != null ? _attrs$style : {};
    });
    function onClick(event) {
      emits("click", event);
    }
    function onDblclick(event) {
      emits("dblclick", event);
    }
    function onMouseEnter(event) {
      emits("mouseenter", event);
    }
    function onMouseMove(event) {
      emits("mousemove", event);
    }
    function onMouseLeave(event) {
      emits("mouseleave", event);
    }
    return (_ctx, _cache) => {
      return !_ctx.hidden && _ctx.dimensions.width !== 0 && _ctx.dimensions.height !== 0 ? (openBlock(), createElementBlock(Fragment, {
        key: 0
      }, [unref(miniMapSlots)["node-" + props.type] ? (openBlock(), createBlock(resolveDynamicComponent(unref(miniMapSlots)["node-" + props.type]), normalizeProps(mergeProps({
        key: 0
      }, _extends({}, props, _ctx.$attrs))), null, 16)) : (openBlock(), createElementBlock("rect", mergeProps({
        key: 1,
        id: _ctx.id
      }, _ctx.$attrs, {
        class: ["vue-flow__minimap-node", {
          selected: _ctx.selected,
          dragging: _ctx.dragging
        }],
        x: _ctx.position.x,
        y: _ctx.position.y,
        rx: _ctx.borderRadius,
        ry: _ctx.borderRadius,
        width: _ctx.dimensions.width,
        height: _ctx.dimensions.height,
        fill: _ctx.color || style.value.background || style.value.backgroundColor,
        stroke: _ctx.strokeColor,
        "stroke-width": _ctx.strokeWidth,
        "shape-rendering": _ctx.shapeRendering,
        onClick,
        onDblclick,
        onMouseenter: onMouseEnter,
        onMousemove: onMouseMove,
        onMouseleave: onMouseLeave
      }), null, 16, _hoisted_1$1$1))], 64)) : createCommentVNode("", true);
    };
  }
}));
const _hoisted_1$6 = ["width", "height", "viewBox", "aria-labelledby"];
const _hoisted_2$6 = ["id"];
const _hoisted_3$6 = ["d", "fill", "stroke", "stroke-width"];
const __default__$2 = {
  name: "MiniMap",
  compatConfig: {
    MODE: 3
  }
};
const _sfc_main = /* @__PURE__ */defineComponent(_extends({}, __default__$2, {
  props: {
    nodeColor: {
      type: [String, Function],
      default: "#e2e2e2"
    },
    nodeStrokeColor: {
      type: [String, Function],
      default: "transparent"
    },
    nodeClassName: {
      type: [String, Function]
    },
    nodeBorderRadius: {
      default: 5
    },
    nodeStrokeWidth: {
      default: 2
    },
    maskColor: {
      default: "rgb(240, 240, 240, 0.6)"
    },
    maskStrokeColor: {
      default: "none"
    },
    maskStrokeWidth: {
      default: 1
    },
    position: {
      default: "bottom-right"
    },
    pannable: {
      type: Boolean,
      default: false
    },
    zoomable: {
      type: Boolean,
      default: false
    },
    width: {},
    height: {},
    ariaLabel: {
      default: "Vue Flow mini map"
    },
    inversePan: {
      type: Boolean,
      default: false
    },
    zoomStep: {
      default: 1
    },
    offsetScale: {
      default: 5
    },
    maskBorderRadius: {
      default: 0
    }
  },
  emits: ["click", "nodeClick", "nodeDblclick", "nodeMouseenter", "nodeMousemove", "nodeMouseleave"],
  setup(__props, _ref3) {
    let emit = _ref3.emit;
    const slots = useSlots();
    const attrs = useAttrs();
    const defaultWidth = 200;
    const defaultHeight = 150;
    const _useVueFlow = useVueFlow(),
      id2 = _useVueFlow.id,
      edges = _useVueFlow.edges,
      viewport = _useVueFlow.viewport,
      translateExtent = _useVueFlow.translateExtent,
      dimensions = _useVueFlow.dimensions,
      emits = _useVueFlow.emits,
      d3Selection = _useVueFlow.d3Selection,
      d3Zoom = _useVueFlow.d3Zoom,
      getNodesInitialized = _useVueFlow.getNodesInitialized;
    const el = ref();
    provide(Slots, slots);
    const elementWidth = toRef(() => {
      var _ref4, _props$width;
      var _a;
      return (_ref4 = (_props$width = __props.width) != null ? _props$width : (_a = attrs.style) == null ? void 0 : _a.width) != null ? _ref4 : defaultWidth;
    });
    const elementHeight = toRef(() => {
      var _ref5, _props$height;
      var _a;
      return (_ref5 = (_props$height = __props.height) != null ? _props$height : (_a = attrs.style) == null ? void 0 : _a.height) != null ? _ref5 : defaultHeight;
    });
    const shapeRendering = typeof window === "undefined" || !!window.chrome ? "crispEdges" : "geometricPrecision";
    const nodeColorFunc = computed(() => typeof __props.nodeColor === "string" ? () => __props.nodeColor : __props.nodeColor);
    const nodeStrokeColorFunc = computed(() => typeof __props.nodeStrokeColor === "string" ? () => __props.nodeStrokeColor : __props.nodeStrokeColor);
    const nodeClassNameFunc = computed(() => typeof __props.nodeClassName === "string" ? () => __props.nodeClassName : typeof __props.nodeClassName === "function" ? __props.nodeClassName : () => "");
    const bb = computed(() => getRectOfNodes(getNodesInitialized.value.filter(node => !node.hidden)));
    const viewBB = computed(() => ({
      x: -viewport.value.x / viewport.value.zoom,
      y: -viewport.value.y / viewport.value.zoom,
      width: dimensions.value.width / viewport.value.zoom,
      height: dimensions.value.height / viewport.value.zoom
    }));
    const boundingRect = computed(() => getNodesInitialized.value && getNodesInitialized.value.length ? getBoundsofRects(bb.value, viewBB.value) : viewBB.value);
    const viewScale = computed(() => {
      const scaledWidth = boundingRect.value.width / elementWidth.value;
      const scaledHeight = boundingRect.value.height / elementHeight.value;
      return Math.max(scaledWidth, scaledHeight);
    });
    const viewBox = computed(() => {
      const viewWidth = viewScale.value * elementWidth.value;
      const viewHeight = viewScale.value * elementHeight.value;
      const offset = __props.offsetScale * viewScale.value;
      return {
        offset,
        x: boundingRect.value.x - (viewWidth - boundingRect.value.width) / 2 - offset,
        y: boundingRect.value.y - (viewHeight - boundingRect.value.height) / 2 - offset,
        width: viewWidth + offset * 2,
        height: viewHeight + offset * 2
      };
    });
    const d = computed(() => {
      if (!viewBox.value.x || !viewBox.value.y) {
        return "";
      }
      return "\n    M" + (viewBox.value.x - viewBox.value.offset) + "," + (viewBox.value.y - viewBox.value.offset) + "\n    h" + (viewBox.value.width + viewBox.value.offset * 2) + "\n    v" + (viewBox.value.height + viewBox.value.offset * 2) + "\n    h" + (-viewBox.value.width - viewBox.value.offset * 2) + "z\n    M" + (viewBB.value.x + __props.maskBorderRadius) + "," + viewBB.value.y + "\n    h" + (viewBB.value.width - 2 * __props.maskBorderRadius) + "\n    a" + __props.maskBorderRadius + "," + __props.maskBorderRadius + " 0 0 1 " + __props.maskBorderRadius + "," + __props.maskBorderRadius + "\n    v" + (viewBB.value.height - 2 * __props.maskBorderRadius) + "\n    a" + __props.maskBorderRadius + "," + __props.maskBorderRadius + " 0 0 1 -" + __props.maskBorderRadius + "," + __props.maskBorderRadius + "\n    h" + -(viewBB.value.width - 2 * __props.maskBorderRadius) + "\n    a" + __props.maskBorderRadius + "," + __props.maskBorderRadius + " 0 0 1 -" + __props.maskBorderRadius + ",-" + __props.maskBorderRadius + "\n    v" + -(viewBB.value.height - 2 * __props.maskBorderRadius) + "\n    a" + __props.maskBorderRadius + "," + __props.maskBorderRadius + " 0 0 1 " + __props.maskBorderRadius + ",-" + __props.maskBorderRadius + "z";
    });
    watchEffect(onCleanup => {
      if (el.value) {
        const selection2 = select(el.value);
        const zoomHandler = event => {
          if (event.sourceEvent.type !== "wheel" || !d3Selection.value || !d3Zoom.value) {
            return;
          }
          const factor = event.sourceEvent.ctrlKey && isMacOs() ? 10 : 1;
          const pinchDelta = -event.sourceEvent.deltaY * (event.sourceEvent.deltaMode === 1 ? 0.05 : event.sourceEvent.deltaMode ? 1 : 2e-3) * __props.zoomStep;
          const nextZoom = viewport.value.zoom * 2 ** (pinchDelta * factor);
          d3Zoom.value.scaleTo(d3Selection.value, nextZoom);
        };
        const panHandler = event => {
          if (event.sourceEvent.type !== "mousemove" || !d3Selection.value || !d3Zoom.value) {
            return;
          }
          const moveScale = viewScale.value * Math.max(1, viewport.value.zoom) * (__props.inversePan ? -1 : 1);
          const position = {
            x: viewport.value.x - event.sourceEvent.movementX * moveScale,
            y: viewport.value.y - event.sourceEvent.movementY * moveScale
          };
          const extent = [[0, 0], [dimensions.value.width, dimensions.value.height]];
          const nextTransform = identity.translate(position.x, position.y).scale(viewport.value.zoom);
          const constrainedTransform = d3Zoom.value.constrain()(nextTransform, extent, translateExtent.value);
          d3Zoom.value.transform(d3Selection.value, constrainedTransform);
        };
        const zoomAndPanHandler = zoom().wheelDelta(event => wheelDelta(event) * (__props.zoomStep / 10)).on("zoom", __props.pannable ? panHandler : () => {}).on("zoom.wheel", __props.zoomable ? zoomHandler : () => {});
        selection2.call(zoomAndPanHandler);
        onCleanup(() => {
          selection2.on("zoom", null);
        });
      }
    }, {
      flush: "post"
    });
    function onSvgClick(event) {
      const _pointer = pointer(event),
        x = _pointer[0],
        y = _pointer[1];
      emit("click", {
        event,
        position: {
          x,
          y
        }
      });
    }
    function onNodeClick(event, node) {
      const param = {
        event,
        node,
        connectedEdges: getConnectedEdges([node], edges.value)
      };
      emits.miniMapNodeClick(param);
      emit("nodeClick", param);
    }
    function onNodeDblClick(event, node) {
      const param = {
        event,
        node,
        connectedEdges: getConnectedEdges([node], edges.value)
      };
      emits.miniMapNodeDoubleClick(param);
      emit("nodeDblclick", param);
    }
    function onNodeMouseEnter(event, node) {
      const param = {
        event,
        node,
        connectedEdges: getConnectedEdges([node], edges.value)
      };
      emits.miniMapNodeMouseEnter(param);
      emit("nodeMouseenter", param);
    }
    function onNodeMouseMove(event, node) {
      const param = {
        event,
        node,
        connectedEdges: getConnectedEdges([node], edges.value)
      };
      emits.miniMapNodeMouseMove(param);
      emit("nodeMousemove", param);
    }
    function onNodeMouseLeave(event, node) {
      const param = {
        event,
        node,
        connectedEdges: getConnectedEdges([node], edges.value)
      };
      emits.miniMapNodeMouseLeave(param);
      emit("nodeMouseleave", param);
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(_sfc_main$g), {
        position: _ctx.position,
        class: normalizeClass(["vue-flow__minimap", {
          pannable: _ctx.pannable,
          zoomable: _ctx.zoomable
        }])
      }, {
        default: withCtx(() => [(openBlock(), createElementBlock("svg", {
          ref_key: "el",
          ref: el,
          width: elementWidth.value,
          height: elementHeight.value,
          viewBox: [viewBox.value.x, viewBox.value.y, viewBox.value.width, viewBox.value.height].join(" "),
          role: "img",
          "aria-labelledby": "vue-flow__minimap-" + unref(id2),
          onClick: onSvgClick
        }, [_ctx.ariaLabel ? (openBlock(), createElementBlock("title", {
          key: 0,
          id: "vue-flow__minimap-" + unref(id2)
        }, toDisplayString(_ctx.ariaLabel), 9, _hoisted_2$6)) : createCommentVNode("", true), (openBlock(true), createElementBlock(Fragment, null, renderList(unref(getNodesInitialized), node => {
          return openBlock(), createBlock(_sfc_main$1, {
            id: node.id,
            key: node.id,
            f: "",
            position: node.computedPosition,
            dimensions: node.dimensions,
            selected: node.selected,
            dragging: node.dragging,
            style: normalizeStyle(node.style),
            class: normalizeClass(nodeClassNameFunc.value(node)),
            color: nodeColorFunc.value(node),
            "border-radius": _ctx.nodeBorderRadius,
            "stroke-color": nodeStrokeColorFunc.value(node),
            "stroke-width": _ctx.nodeStrokeWidth,
            "shape-rendering": unref(shapeRendering),
            type: node.type,
            hidden: node.hidden,
            onClick: $event => onNodeClick($event, node),
            onDblclick: $event => onNodeDblClick($event, node),
            onMouseenter: $event => onNodeMouseEnter($event, node),
            onMousemove: $event => onNodeMouseMove($event, node),
            onMouseleave: $event => onNodeMouseLeave($event, node)
          }, null, 8, ["id", "position", "dimensions", "selected", "dragging", "style", "class", "color", "border-radius", "stroke-color", "stroke-width", "shape-rendering", "type", "hidden", "onClick", "onDblclick", "onMouseenter", "onMousemove", "onMouseleave"]);
        }), 128)), createBaseVNode("path", {
          class: "vue-flow__minimap-mask",
          d: d.value,
          fill: _ctx.maskColor,
          stroke: _ctx.maskStrokeColor,
          "stroke-width": _ctx.maskStrokeWidth,
          "fill-rule": "evenodd"
        }, null, 8, _hoisted_3$6)], 8, _hoisted_1$6))]),
        _: 1
      }, 8, ["position", "class"]);
    };
  }
}));

var script$5 = {
  name: 'StageNode',
  components: { Handle: _sfc_main$f },
  props: {
    data: {
      type: Object,
      required: true,
    },
  },
  setup() {
    const { connectionStartHandle } = useVueFlow();
    return { connectionStartHandle };
  },
  data() {
    return {
      showActions: false,
      isHoveredOrFocused: false,
      hoverTimeout: null,
      blurTimeout: null,
    };
  },
  computed: {
    isConnecting() {
      return !!this.connectionStartHandle;
    },
    Position() {
      return Position;
    },
    stage() {
      return this.data.stage;
    },
    isSelected() {
      return this.data.isSelected;
    },
    stageStyle() {
      return {
        borderColor: 'var(--code-color) !important',
        borderWidth: this.isSelected ? '3px !important' : '0 !important',
        background: this.data.isSpecial ? 'purple !important' : 'rgb(var(--primary-rgb)) !important',
        padding: this.isSelected ? '4px !important' : '6px !important',
      };
    },
    onSelected() {
      return this.data.onSelect?.();
    },
    onEscape() {
      return this.data.onEscape?.();
    },
  },
  methods: {
    toggleActions() {
      if (this.showActions) {
        this.closeActions();
      } else {
        this.openActions();
      }
    },
    openActions() {
      this.data.onSelect?.();
      this.showActions = true;
      this.$nextTick(() => {
        // Focus first available action
        const firstButton = this.$refs.editButton || this.$refs.deleteButton;
        if (firstButton) {
          firstButton.focus();
        }
      });
      document.addEventListener('click', this.closeActions);
    },
    closeActions() {
      if (this.showActions) {
        document.removeEventListener('click', this.closeActions);
      }
      this.data.onEscape?.();
      clearTimeout(this.hoverTimeout);
      clearTimeout(this.blurTimeout);
      this.showActions = false;
      // Return focus to menu button
      this.$nextTick(() => {
        if (this.$refs.menuButton) {
          this.$refs.menuButton.focus();
        }
      });
    },
    handleEdit() {
      this.closeActions();
      this.data?.onEdit?.();
    },
    handleDelete() {
      this.closeActions();
      this.data?.onDelete?.();
    },
    onNodeEnter() {
      clearTimeout(this.hoverTimeout);
      this.isHoveredOrFocused = true;
    },
    onNodeLeave() {
      this.hoverTimeout = setTimeout(() => {
        if (!this.showActions) this.isHoveredOrFocused = false;
      }, 100);
    },
    onDropdownEnter() {
      clearTimeout(this.blurTimeout);
    },
  },
};

const _hoisted_1$5 = ["data-stage-id", "aria-describedby"];
const _hoisted_2$5 = {
  key: 0,
  class: "position-absolute top-25-px end-20-px h-100 rounded bg-secondary bg-opacity-75 z-2 pe-none",
  "aria-hidden": "true"
};
const _hoisted_3$5 = ["id", "aria-labelledby"];
const _hoisted_4$4 = { class: "visually-hidden" };
const _hoisted_5$4 = ["title"];
const _hoisted_6$3 = ["title"];
const _hoisted_7$3 = {
  key: 2,
  class: "stage-handles",
  "aria-hidden": "true"
};
const _hoisted_8$3 = { class: "card-header d-flex justify-content-between align-items-start p-1 pe-0 z-1 position-relative" };
const _hoisted_9$3 = { class: "flex-fill w-75 me-3" };
const _hoisted_10$2 = ["title"];
const _hoisted_11$2 = ["id", "title"];
const _hoisted_12$2 = {
  key: 0,
  class: "stage-card-actions align-items-center d-flex position-relative"
};
const _hoisted_13$2 = ["id", "title", "aria-expanded", "aria-controls"];
const _hoisted_14$2 = { class: "visually-hidden" };
const _hoisted_15$1 = { class: "card-body px-1 py-0 z-1 position-relative" };
const _hoisted_16$1 = { class: "d-flex justify-content-between align-items-center" };
const _hoisted_17$1 = {
  key: 0,
  class: "badge bg-warning bg-opacity-10 rounded-pill p-1"
};

function render$4(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Handle = resolveComponent("Handle");

  return (openBlock(), createElementBlock("div", {
    class: "stage-node card border shadow-sm position-relative",
    tabindex: "0",
    role: "button",
    style: normalizeStyle($options.stageStyle),
    "data-stage-id": $options.stage?.id,
    "aria-describedby": `stage-${$options.stage?.id}-description`,
    onMouseenter: _cache[10] || (_cache[10] = (...args) => ($options.onNodeEnter && $options.onNodeEnter(...args))),
    onMouseleave: _cache[11] || (_cache[11] = (...args) => ($options.onNodeLeave && $options.onNodeLeave(...args))),
    onClick: _cache[12] || (_cache[12] = (...args) => ($options.onSelected && $options.onSelected(...args))),
    onKeydown: [
      _cache[13] || (_cache[13] = withKeys(withModifiers((...args) => ($options.openActions && $options.openActions(...args)), ["stop","prevent"]), ["enter"])),
      _cache[14] || (_cache[14] = withKeys(withModifiers((...args) => ($options.openActions && $options.openActions(...args)), ["prevent","stop"]), ["space"])),
      _cache[15] || (_cache[15] = withKeys((...args) => ($options.closeActions && $options.closeActions(...args)), ["esc"])),
      _cache[16] || (_cache[16] = withKeys((...args) => ($options.closeActions && $options.closeActions(...args)), ["tab"]))
    ]
  }, [
    createCommentVNode(" Dropdown Overlay "),
    ($data.showActions)
      ? (openBlock(), createElementBlock("div", _hoisted_2$5))
      : createCommentVNode("v-if", true),
    createCommentVNode(" Actions Dropdown "),
    ($data.showActions)
      ? (openBlock(), createElementBlock("nav", {
          key: 1,
          id: `stage-actions-menu-${$options.stage?.id}`,
          ref: "actionsMenu",
          class: "workflow-browser-actions-list position-absolute top-25-px end-20-px opacity-100 d-flex flex-column border rounded shadow-sm z-3 p-1",
          "aria-orientation": "vertical",
          "aria-labelledby": `stage-${$options.stage?.id}-menu-button`,
          onMouseenter: _cache[6] || (_cache[6] = (...args) => ($options.onDropdownEnter && $options.onDropdownEnter(...args)))
        }, [
          createBaseVNode("span", _hoisted_4$4, toDisplayString(_ctx.sprintf('COM_WORKFLOW_GRAPH_STAGE_ACTIONS', $options.stage?.title)), 1 /* TEXT */),
          ($options.stage?.permissions?.edit)
            ? (openBlock(), createElementBlock("button", {
                key: 0,
                ref: "editButton",
                class: "btn btn-sm btn-secondary text-start text-white fw-semibold text-truncate",
                role: "menuitem",
                tabindex: "0",
                title: _ctx.translate('COM_WORKFLOW_GRAPH_EDIT_STAGE'),
                onClick: _cache[0] || (_cache[0] = (...args) => ($options.handleEdit && $options.handleEdit(...args))),
                onKeydown: [
                  _cache[1] || (_cache[1] = withKeys((...args) => ($options.handleEdit && $options.handleEdit(...args)), ["enter"])),
                  _cache[2] || (_cache[2] = withKeys((...args) => ($options.handleEdit && $options.handleEdit(...args)), ["space"]))
                ]
              }, [
                _cache[17] || (_cache[17] = createBaseVNode("span", {
                  class: "icon icon-pencil-alt me-1",
                  "aria-hidden": "true"
                }, null, -1 /* CACHED */)),
                createTextVNode(" " + toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_EDIT_STAGE')), 1 /* TEXT */)
              ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_5$4))
            : createCommentVNode("v-if", true),
          ($options.stage?.permissions?.delete && !$options.stage.default)
            ? (openBlock(), createElementBlock("button", {
                key: 1,
                ref: "deleteButton",
                class: "btn btn-sm btn-danger mt-1 text-start text-white fw-semibold text-truncate",
                role: "menuitem",
                tabindex: "0",
                title: _ctx.translate('COM_WORKFLOW_GRAPH_TRASH_STAGE'),
                onClick: _cache[3] || (_cache[3] = (...args) => ($options.handleDelete && $options.handleDelete(...args))),
                onKeydown: [
                  _cache[4] || (_cache[4] = withKeys((...args) => ($options.handleDelete && $options.handleDelete(...args)), ["enter"])),
                  _cache[5] || (_cache[5] = withKeys(withModifiers((...args) => ($options.handleDelete && $options.handleDelete(...args)), ["prevent","stop"]), ["space"]))
                ]
              }, [
                _cache[18] || (_cache[18] = createBaseVNode("span", {
                  class: "icon icon-trash me-1",
                  "aria-hidden": "true"
                }, null, -1 /* CACHED */)),
                createTextVNode(" " + toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_TRASH_STAGE')), 1 /* TEXT */)
              ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_6$3))
            : createCommentVNode("v-if", true)
        ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_3$5))
      : createCommentVNode("v-if", true),
    createCommentVNode(" Connection Handles "),
    ($options.stage.published)
      ? (openBlock(), createElementBlock("div", _hoisted_7$3, [
          createVNode(_component_Handle, {
            type: "target",
            class: normalizeClass(["edge-handler bg-success position-absolute top-0 start-50 translate-middle-x rounded-circle", { 'invisible': !$data.isHoveredOrFocused || $data.showActions || !$options.isConnecting }]),
            position: $options.Position.Top,
            "aria-hidden": "true"
          }, null, 8 /* PROPS */, ["class", "position"]),
          createVNode(_component_Handle, {
            type: "source",
            class: normalizeClass(["edge-handler bg-success position-absolute bottom-0 start-50 translate-middle-x rounded-circle", { 'invisible': !$data.isHoveredOrFocused || $data.showActions }]),
            position: $options.Position.Bottom,
            "aria-hidden": "true"
          }, null, 8 /* PROPS */, ["class", "position"]),
          createVNode(_component_Handle, {
            type: "target",
            class: normalizeClass(["edge-handler bg-success position-absolute top-50 start-0 translate-middle-y rounded-circle", { 'invisible': !$data.isHoveredOrFocused || $data.showActions ||!$options.isConnecting }]),
            position: $options.Position.Left,
            "aria-hidden": "true"
          }, null, 8 /* PROPS */, ["class", "position"]),
          createVNode(_component_Handle, {
            type: "source",
            class: normalizeClass(["edge-handler bg-success position-absolute top-50 end-0 translate-middle-y rounded-circle", { 'invisible': !$data.isHoveredOrFocused || $data.showActions }]),
            position: $options.Position.Right,
            "aria-hidden": "true"
          }, null, 8 /* PROPS */, ["position", "class"])
        ]))
      : createCommentVNode("v-if", true),
    createBaseVNode("div", _hoisted_8$3, [
      createBaseVNode("div", _hoisted_9$3, [
        createBaseVNode("span", {
          class: "h3 d-block card-title mb-1 text-white fw-semibold text-truncate",
          title: _ctx.translate($options.stage?.title)
        }, toDisplayString(_ctx.translate($options.stage.title)), 9 /* TEXT, PROPS */, _hoisted_10$2),
        createBaseVNode("span", {
          id: `stage-${$options.stage?.id}-description`,
          class: "card-text text-white-50 mb-0 text-truncate d-block",
          title: _ctx.translate($options.stage?.description ? $options.stage.description : '')
        }, toDisplayString(_ctx.translate($options.stage.description ? $options.stage.description : '')), 9 /* TEXT, PROPS */, _hoisted_11$2)
      ]),
      createCommentVNode(" Actions Button "),
      (!$props.data?.isSpecial)
        ? (openBlock(), createElementBlock("div", _hoisted_12$2, [
            createBaseVNode("button", {
              id: `stage-${$options.stage?.id}-menu-button`,
              ref: "menuButton",
              class: normalizeClass(["btn btn-sm btn-light px-1 py-0", { 'invisible': !$data.isHoveredOrFocused && !$data.showActions }]),
              style: {"transition":"opacity 0.2s ease"},
              title: $data.showActions ? _ctx.sprintf('COM_WORKFLOW_GRAPH_CLOSE_ACTIONS_MENU', $options.stage?.title) : _ctx.sprintf('COM_WORKFLOW_GRAPH_OPEN_ACTIONS_MENU', $options.stage?.title),
              "aria-haspopup": "true",
              "aria-expanded": $data.showActions,
              "aria-controls": `stage-actions-menu-${$options.stage?.id}`,
              onClick: _cache[7] || (_cache[7] = withModifiers((...args) => ($options.toggleActions && $options.toggleActions(...args)), ["stop"])),
              onKeydown: [
                _cache[8] || (_cache[8] = withKeys(withModifiers((...args) => ($options.toggleActions && $options.toggleActions(...args)), ["stop"]), ["enter"])),
                _cache[9] || (_cache[9] = withKeys(withModifiers((...args) => ($options.toggleActions && $options.toggleActions(...args)), ["prevent","stop"]), ["space"]))
              ]
            }, [
              createBaseVNode("span", {
                class: normalizeClass($data.showActions ? 'icon icon-times' : 'icon icon-ellipsis-h'),
                "aria-hidden": "true"
              }, null, 2 /* CLASS */),
              createBaseVNode("span", _hoisted_14$2, toDisplayString($data.showActions ? _ctx.sprintf('COM_WORKFLOW_GRAPH_CLOSE_ACTIONS_MENU', $options.stage?.title) : _ctx.sprintf('COM_WORKFLOW_GRAPH_OPEN_ACTIONS_MENU', $options.stage?.title)), 1 /* TEXT */)
            ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_13$2)
          ]))
        : createCommentVNode("v-if", true)
    ]),
    createCommentVNode(" Body "),
    createBaseVNode("div", _hoisted_15$1, [
      createBaseVNode("div", _hoisted_16$1, [
        createBaseVNode("span", {
          class: normalizeClass([$options.stage.published ? 'bg-success' : 'bg-danger', "badge rounded-pill p-1"])
        }, toDisplayString($options.stage.published ? _ctx.translate('COM_WORKFLOW_GRAPH_ENABLED') : _ctx.translate('COM_WORKFLOW_GRAPH_DISABLED')), 3 /* TEXT, CLASS */),
        ($options.stage.default)
          ? (openBlock(), createElementBlock("span", _hoisted_17$1, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_DEFAULT')), 1 /* TEXT */))
          : createCommentVNode("v-if", true)
      ])
    ])
  ], 44 /* STYLE, PROPS, NEED_HYDRATION */, _hoisted_1$5))
}

script$5.render = render$4;
script$5.__file = "media_source/com_workflow/src/components/nodes/StageNode.vue";

var script$4 = {
  name: 'CustomEdge',
  components: { EdgeLabelRenderer: _sfc_main$3 },
  props: {
    id: { type: String, default: '' },
    sourceX: { type: Number, default: 0 },
    sourceY: { type: Number, default: 0 },
    targetX: { type: Number, default: 0 },
    targetY: { type: Number, default: 0 },
    sourcePosition: { type: String, default: '' },
    targetPosition: { type: String, default: '' },
    style: { type: Object, default: () => ({}) },
    markerEnd: { type: Object, default: () => ({}) },
    data: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      showActions: false,
      isHovered: false,
      maxWidth: 100,
      blurTimeout: null,
      hoverTimeout: null,
    };
  },
  computed: {
    edgeData() {
      // Use offsetIndex to curve the edge away from overlapping others
      const offsetIndex = this.data?.offsetIndex || 0;

      // Calculate perpendicular offset direction
      const dx = this.targetX - this.sourceX;
      const dy = this.targetY - this.sourceY;
      const length = Math.sqrt(dx * dx + dy * dy) || 1;

      // Perpendicular vector
      const perpX = -dy / length;
      const perpY = dx / length;
      const curveMagnitude = 40 * offsetIndex;

      // Control point for Bezier curve
      const centerX = (this.sourceX + this.targetX) / 2 + perpX * curveMagnitude;
      const centerY = (this.sourceY + this.targetY) / 2 + perpY * curveMagnitude;
      return getSmoothStepPath({
        sourceX: this.sourceX,
        sourceY: this.sourceY,
        targetX: this.targetX,
        targetY: this.targetY,
        sourcePosition: this.sourcePosition,
        targetPosition: this.targetPosition,
        centerX,
        centerY,
        borderRadius: 10,
        offset: 10,
      });
    },
    edgePath() {
      return this.edgeData[0];
    },
    labelX() {
      return this.edgeData[1] + ((this.data?.isBiDirectional && this.sourceY < this.targetY && this.data?.offsetIndex ? this.data?.offsetIndex : 0) || 0) * this.maxWidth;
    },
    labelY() {
      return this.edgeData[2] + ((this.data?.isBiDirectional && this.sourceY > this.targetY && this.data?.offsetIndex ? this.data?.offsetIndex : 0) || 0) * 75;
    },
    sourceStageTitle() {
      return this.data?.from_stage_title || `JSTAGE ${this.data?.from_stage_id || 'Unknown'}`;
    },
    targetStageTitle() {
      return this.data?.to_stage_title || `JSTAGE ${this.data?.to_stage_id || 'Unknown'}`;
    },
    menuItems() {
      const items = [];
      if (this.data?.permissions?.edit && this.$refs.editButton) items.push(this.$refs.editButton);
      if (this.data?.permissions?.delete && this.$refs.deleteButton) items.push(this.$refs.deleteButton);
      return items;
    },
  },
  watch: {
    'data.title': {
      handler: 'updateLabelWidth',
      immediate: true,
    },
  },
  mounted() {
    this.updateLabelWidth();
  },
  beforeUnmount() {
    if (this.blurTimeout) clearTimeout(this.blurTimeout);
    if (this.hoverTimeout) clearTimeout(this.hoverTimeout);
  },
  methods: {
    toggleActions() {
      this.showActions ? this.closeActions() : this.openActions();
    },
    openActions() {
      this.data?.onSelect?.();
      this.showActions = true;
      this.$nextTick(() => {
        // Focus first available action
        const firstButton = this.$refs.editButton || this.$refs.deleteButton;
        if (firstButton) {
          firstButton.focus();
        }
      });
      document.addEventListener('click', this.closeActions);
    },
    closeActions() {
      if (this.showActions) {
        document.removeEventListener('click', this.closeActions);
      }
      this.data.onEscape?.();
      clearTimeout(this.hoverTimeout);
      clearTimeout(this.blurTimeout);
      this.showActions = false;
      // Return focus to menu button
      this.$nextTick(() => {
        if (this.$refs.menuButton) {
          this.$refs.menuButton.focus();
        }
      });
    },
    handleEdit() {
      this.closeActions();
      this.data?.onEdit?.();
    },
    handleDelete() {
      this.closeActions();
      this.data?.onDelete?.();
    },
    onNodeEnter() {
      clearTimeout(this.hoverTimeout);
      this.isHovered = true;
    },
    onNodeLeave() {
      this.hoverTimeout = setTimeout(() => {
        if (!this.showActions) this.isHovered = false;
      }, 100);
    },
    onDropdownEnter() {
      clearTimeout(this.blurTimeout);
    },
    onSelected() {
      return this.data?.onSelect?.();
    },
    updateLabelWidth() {
      this.$nextTick(() => {
        if (this.$refs.textMeasurer) {
          const measuredWidth = this.$refs.textMeasurer.offsetWidth;
          this.maxWidth = Math.min(measuredWidth + 50, 300);
        }
      });
    },
  },
};

const _hoisted_1$4 = ["aria-label"];
const _hoisted_2$4 = ["d", "aria-label", "stroke", "stroke-width", "stroke-dasharray", "marker-end"];
const _hoisted_3$4 = ["data-edge-id", "aria-pressed"];
const _hoisted_4$3 = {
  key: 0,
  class: "position-absolute top-25-px end-20-px h-100 rounded bg-secondary bg-opacity-75 pe-none",
  "aria-hidden": "true"
};
const _hoisted_5$3 = { class: "custom-edge d-flex flex-column border rounded shadow-sm position-absolute" };
const _hoisted_6$2 = ["id", "aria-labelledby"];
const _hoisted_7$2 = { class: "visually-hidden" };
const _hoisted_8$2 = ["title"];
const _hoisted_9$2 = ["title"];
const _hoisted_10$1 = { class: "d-flex justify-content-around align-items-center p-1 pe-1 z-1 position-relative" };
const _hoisted_11$1 = ["title"];
const _hoisted_12$1 = { class: "align-items-center d-flex position-relative" };
const _hoisted_13$1 = ["id", "title", "aria-expanded", "aria-controls"];
const _hoisted_14$1 = { class: "visually-hidden" };

function render$3(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_EdgeLabelRenderer = resolveComponent("EdgeLabelRenderer");

  return (openBlock(), createElementBlock("g", {
    role: "group",
    "aria-label": _ctx.sprintf('COM_WORKFLOW_GRAPH_TRANSITION', $props.data?.title, $options.sourceStageTitle, $options.targetStageTitle)
  }, [
    createBaseVNode("path", {
      d: $options.edgePath,
      fill: "none",
      role: "img",
      "aria-label": _ctx.sprintf('COM_WORKFLOW_GRAPH_TRANSITION_PATH', $props.data?.title),
      stroke: $props.style?.stroke || '#333',
      "stroke-width": $props.style?.strokeWidth || 2,
      "stroke-dasharray": $props.style?.strokeDasharray,
      "marker-end": $props.markerEnd
    }, null, 8 /* PROPS */, _hoisted_2$4),
    createVNode(_component_EdgeLabelRenderer, null, {
      default: withCtx(() => [
        createBaseVNode("div", {
          ref: "edgeLabel",
          class: "edge-label position-absolute cursor-pointer",
          tabindex: "0",
          role: "button",
          "data-edge-id": $props.data?.id,
          "aria-pressed": $props.data?.isSelected ? 'true' : 'false',
          style: normalizeStyle({
          width: $data.maxWidth + 10 + 'px',
          height: '30px',
          transform: 'translate(-50%, -50%)',
          left: $options.labelX + 'px',
          top: $options.labelY + 'px',
          pointerEvents: 'all',
          zIndex: $data.showActions ? 20 : 10,
        }),
          onMouseenter: _cache[10] || (_cache[10] = (...args) => ($options.onNodeEnter && $options.onNodeEnter(...args))),
          onMouseleave: _cache[11] || (_cache[11] = (...args) => ($options.onNodeLeave && $options.onNodeLeave(...args))),
          onClick: _cache[12] || (_cache[12] = (...args) => ($options.onSelected && $options.onSelected(...args))),
          onKeydown: [
            _cache[13] || (_cache[13] = withKeys(withModifiers((...args) => ($options.openActions && $options.openActions(...args)), ["stop","prevent"]), ["enter"])),
            _cache[14] || (_cache[14] = withKeys(withModifiers((...args) => ($options.openActions && $options.openActions(...args)), ["prevent","stop"]), ["space"])),
            _cache[15] || (_cache[15] = withKeys((...args) => ($options.closeActions && $options.closeActions(...args)), ["esc"])),
            _cache[16] || (_cache[16] = withKeys((...args) => ($options.closeActions && $options.closeActions(...args)), ["tab"]))
          ]
        }, [
          ($data.showActions)
            ? (openBlock(), createElementBlock("div", _hoisted_4$3))
            : createCommentVNode("v-if", true),
          createBaseVNode("div", _hoisted_5$3, [
            ($data.showActions)
              ? (openBlock(), createElementBlock("nav", {
                  key: 0,
                  id: `edge-actions-menu-${$props.data?.id}`,
                  ref: "actionsMenu",
                  class: "workflow-browser-actions-list position-absolute top-25-px end-20-px opacity-100 d-flex flex-column border rounded shadow-sm p-1",
                  role: "menu",
                  "aria-orientation": "vertical",
                  "aria-labelledby": `transition-${$props.data?.id}-menu-button`,
                  onMouseenter: _cache[6] || (_cache[6] = (...args) => ($options.onDropdownEnter && $options.onDropdownEnter(...args)))
                }, [
                  createBaseVNode("span", _hoisted_7$2, toDisplayString(_ctx.sprintf('COM_WORKFLOW_GRAPH_TRANSITION_ACTIONS', $props.data?.title)), 1 /* TEXT */),
                  ($props.data?.permissions?.edit)
                    ? (openBlock(), createElementBlock("button", {
                        key: 0,
                        ref: "editButton",
                        class: "btn btn-sm btn-secondary text-start text-white fw-semibold text-truncate",
                        role: "menuitem",
                        tabindex: "0",
                        title: _ctx.translate('COM_WORKFLOW_GRAPH_EDIT_TRANSITION'),
                        onClick: _cache[0] || (_cache[0] = (...args) => ($options.handleEdit && $options.handleEdit(...args))),
                        onKeydown: [
                          _cache[1] || (_cache[1] = withKeys(withModifiers((...args) => ($options.handleEdit && $options.handleEdit(...args)), ["stop","prevent"]), ["enter"])),
                          _cache[2] || (_cache[2] = withKeys(withModifiers((...args) => ($options.handleEdit && $options.handleEdit(...args)), ["prevent","stop"]), ["space"]))
                        ]
                      }, [
                        _cache[17] || (_cache[17] = createBaseVNode("span", {
                          class: "icon icon-pencil-alt me-1",
                          "aria-hidden": "true"
                        }, null, -1 /* CACHED */)),
                        createTextVNode(" " + toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_EDIT_TRANSITION')), 1 /* TEXT */)
                      ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_8$2))
                    : createCommentVNode("v-if", true),
                  ($props.data?.permissions?.delete)
                    ? (openBlock(), createElementBlock("button", {
                        key: 1,
                        ref: "deleteButton",
                        class: "btn btn-sm btn-danger text-start mt-1 text-white fw-semibold text-truncate",
                        role: "menuitem",
                        tabindex: "0",
                        title: _ctx.translate('COM_WORKFLOW_GRAPH_TRASH_TRANSITION'),
                        onClick: _cache[3] || (_cache[3] = (...args) => ($options.handleDelete && $options.handleDelete(...args))),
                        onKeydown: [
                          _cache[4] || (_cache[4] = withKeys(withModifiers((...args) => ($options.handleDelete && $options.handleDelete(...args)), ["stop","prevent"]), ["enter"])),
                          _cache[5] || (_cache[5] = withKeys(withModifiers((...args) => ($options.handleDelete && $options.handleDelete(...args)), ["prevent","stop"]), ["space"]))
                        ]
                      }, [
                        _cache[18] || (_cache[18] = createBaseVNode("span", {
                          class: "icon icon-trash me-1",
                          "aria-hidden": "true"
                        }, null, -1 /* CACHED */)),
                        createTextVNode(" " + toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_TRASH_TRANSITION')), 1 /* TEXT */)
                      ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_9$2))
                    : createCommentVNode("v-if", true)
                ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_6$2))
              : createCommentVNode("v-if", true),
            createBaseVNode("div", _hoisted_10$1, [
              createBaseVNode("span", {
                class: "h4 d-block card-title text-white fw-semibold text-truncate ms-4",
                title: _ctx.translate($props.data?.title)
              }, toDisplayString(_ctx.translate($props.data?.title)), 9 /* TEXT, PROPS */, _hoisted_11$1),
              createBaseVNode("div", _hoisted_12$1, [
                createBaseVNode("button", {
                  id: `transition-${$props.data?.id}-menu-button`,
                  ref: "menuButton",
                  class: normalizeClass(["btn btn-sm btn-secondary ms-1 px-1 py-0", { 'invisible': !$data.isHovered && !$data.showActions }]),
                  style: normalizeStyle({
                  transition: 'opacity 0.2s ease',
                  zIndex: $data.showActions ? 30 : 11,
                  position: 'relative',
                }),
                  title: $data.showActions ? _ctx.sprintf('COM_WORKFLOW_GRAPH_CLOSE_ACTIONS_MENU', $props.data?.title) : _ctx.sprintf('COM_WORKFLOW_GRAPH_OPEN_ACTIONS_MENU', $props.data?.title),
                  "aria-haspopup": "true",
                  "aria-expanded": $data.showActions,
                  "aria-controls": `edge-actions-menu-${$props.data?.id}`,
                  onClick: _cache[7] || (_cache[7] = withModifiers((...args) => ($options.toggleActions && $options.toggleActions(...args)), ["stop"])),
                  onKeydown: [
                    _cache[8] || (_cache[8] = withKeys(withModifiers((...args) => ($options.toggleActions && $options.toggleActions(...args)), ["stop"]), ["enter"])),
                    _cache[9] || (_cache[9] = withKeys(withModifiers((...args) => ($options.toggleActions && $options.toggleActions(...args)), ["prevent","stop"]), ["space"]))
                  ]
                }, [
                  createBaseVNode("span", {
                    class: normalizeClass($data.showActions ? 'icon icon-times' : 'icon icon-ellipsis-h'),
                    "aria-hidden": "true"
                  }, null, 2 /* CLASS */),
                  createBaseVNode("span", _hoisted_14$1, toDisplayString($data.showActions ? _ctx.sprintf('COM_WORKFLOW_GRAPH_CLOSE_ACTIONS_MENU', $props.data?.title) : _ctx.sprintf('COM_WORKFLOW_GRAPH_OPEN_ACTIONS_MENU', $props.data?.title)), 1 /* TEXT */)
                ], 46 /* CLASS, STYLE, PROPS, NEED_HYDRATION */, _hoisted_13$1)
              ])
            ])
          ]),
          createCommentVNode(" Hidden measurer "),
          createBaseVNode("span", {
            ref: "textMeasurer",
            class: "fw-semibold invisible position-absolute",
            style: {"white-space":"nowrap","font-size":"1rem","font-family":"inherit"},
            "aria-hidden": "true"
          }, toDisplayString(_ctx.translate($props.data?.title)), 513 /* TEXT, NEED_PATCH */)
        ], 44 /* STYLE, PROPS, NEED_HYDRATION */, _hoisted_3$4)
      ]),
      _: 1 /* STABLE */
    })
  ], 8 /* PROPS */, _hoisted_1$4))
}

script$4.render = render$3;
script$4.__file = "media_source/com_workflow/src/components/edges/CustomEdge.vue";

var script$3 = {
  name: 'CustomControls',
  setup() {
    const { zoomIn, zoomOut, fitView } = useVueFlow();
    function customFitView() {
      fitView({
        padding: 0.5,
        duration: 300,
      });
    }
    const controlsContainer = ref(null);

    return {
      zoomIn,
      zoomOut,
      controlsContainer,
      customFitView,
    };
  },
};

const _hoisted_1$3 = {
  ref: "controlsContainer",
  class: "custom-controls position-absolute z-10 p-1",
  role: "list",
  "aria-labelledby": "canvas-controls-title"
};
const _hoisted_2$3 = {
  id: "canvas-controls-title",
  class: "visually-hidden"
};
const _hoisted_3$3 = { class: "d-flex flex-column gap-1 list-unstyled mb-0 px-0" };
const _hoisted_4$2 = ["title"];
const _hoisted_5$2 = { class: "visually-hidden" };
const _hoisted_6$1 = ["title"];
const _hoisted_7$1 = { class: "visually-hidden" };
const _hoisted_8$1 = ["title"];
const _hoisted_9$1 = { class: "visually-hidden" };

function render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return (openBlock(), createElementBlock("div", _hoisted_1$3, [
    createBaseVNode("span", _hoisted_2$3, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_CANVAS_VIEW_CONTROLS')), 1 /* TEXT */),
    createBaseVNode("ul", _hoisted_3$3, [
      createBaseVNode("li", null, [
        createBaseVNode("button", {
          class: "toolbar-button custom-controls-button",
          title: _ctx.translate('COM_WORKFLOW_GRAPH_ZOOM_IN'),
          onClick: _cache[0] || (_cache[0] = (...args) => ($setup.zoomIn && $setup.zoomIn(...args))),
          onKeydown: [
            _cache[1] || (_cache[1] = withKeys((...args) => ($setup.zoomIn && $setup.zoomIn(...args)), ["enter"])),
            _cache[2] || (_cache[2] = withKeys(withModifiers((...args) => ($setup.zoomIn && $setup.zoomIn(...args)), ["prevent"]), ["space"]))
          ]
        }, [
          _cache[9] || (_cache[9] = createBaseVNode("span", {
            class: "icon icon-plus",
            "aria-hidden": "true"
          }, null, -1 /* CACHED */)),
          createBaseVNode("span", _hoisted_5$2, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_ZOOM_IN')), 1 /* TEXT */)
        ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_4$2)
      ]),
      createBaseVNode("li", null, [
        createBaseVNode("button", {
          class: "toolbar-button custom-controls-button",
          title: _ctx.translate('COM_WORKFLOW_GRAPH_ZOOM_OUT'),
          onClick: _cache[3] || (_cache[3] = (...args) => ($setup.zoomOut && $setup.zoomOut(...args))),
          onKeydown: [
            _cache[4] || (_cache[4] = withKeys((...args) => ($setup.zoomOut && $setup.zoomOut(...args)), ["enter"])),
            _cache[5] || (_cache[5] = withKeys(withModifiers((...args) => ($setup.zoomOut && $setup.zoomOut(...args)), ["prevent"]), ["space"]))
          ]
        }, [
          _cache[10] || (_cache[10] = createBaseVNode("span", {
            class: "icon icon-minus",
            "aria-hidden": "true"
          }, null, -1 /* CACHED */)),
          createBaseVNode("span", _hoisted_7$1, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_ZOOM_OUT')), 1 /* TEXT */)
        ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_6$1)
      ]),
      createBaseVNode("li", null, [
        createBaseVNode("button", {
          class: "toolbar-button custom-controls-button",
          title: _ctx.translate('COM_WORKFLOW_GRAPH_FIT_VIEW'),
          onClick: _cache[6] || (_cache[6] = (...args) => ($setup.customFitView && $setup.customFitView(...args))),
          onKeydown: [
            _cache[7] || (_cache[7] = withKeys((...args) => ($setup.customFitView && $setup.customFitView(...args)), ["enter"])),
            _cache[8] || (_cache[8] = withKeys(withModifiers((...args) => ($setup.customFitView && $setup.customFitView(...args)), ["prevent"]), ["space"]))
          ]
        }, [
          _cache[11] || (_cache[11] = createBaseVNode("span", {
            class: "icon icon-expand",
            "aria-hidden": "true"
          }, null, -1 /* CACHED */)),
          createBaseVNode("span", _hoisted_9$1, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_FIT_VIEW')), 1 /* TEXT */)
        ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_8$1)
      ])
    ])
  ], 512 /* NEED_PATCH */))
}

script$3.render = render$2;
script$3.__file = "media_source/com_workflow/src/components/canvas/CustomControls.vue";

/**
 * Utility function to compute color for a stage based on its ID.
 * Uses a hue offset to ensure color uniqueness.
 * @param {Object} stage - Stage object with an `id` field.
 * @returns {string} HSL color string.
 */
function getColorForStage(stage) {
  return (stage == null ? void 0 : stage.color) || 'rgb(var(--primary-rgb))';
}

/**
 * Utility function to compute color for a transition based on its ID.
 * Uses a different hue offset than stages.
 * @param {Object} transition - Transition object with an `id` field.
 * @returns {string} HSL color string.
 */
function getColorForTransition(transition) {
  const hue = parseInt(transition == null ? void 0 : transition.id, 10) * 199 % 360;
  return "hsl(" + hue + ", 70%, 60%)";
}

/**
 * Utility function to determine edge color for a transition.
 * @param {Object} transition - Transition object.
 * @param {boolean} isSelected - Whether the edge is currently selected.
 * @returns {string} Hex or HSL color.
 */
function getEdgeColor(transition, isSelected) {
  if (isSelected) return getColorForTransition(transition); // Blue for selected
  if (transition != null && transition.published) return '#3B82F6';
  return transition.from_stage_id === -1 || transition.to_stage_id === -1 ? '#F97316' : '#10B981';
}

/**
 * Utility function to debounce a function call by delay in milliseconds.
 * Useful for rate-limiting input or UI updates.
 * @param {Function} func - Function to debounce.
 * @param {number} delay - Delay in milliseconds.
 * @returns {Function} Debounced function.
 */
function debounce(func, delay) {
  let timer;
  return function debounced() {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    clearTimeout(timer);
    timer = setTimeout(() => func.apply(this, args), delay);
  };
}

/**
 * Utility function to check if the document is in right-to-left (RTL) mode.
 * @returns {boolean} True if RTL, false otherwise.
 */
function isRTL() {
  return document.dir === 'rtl';
}

var script$2 = {
  name: 'ControlsPanel',
  components: { Panel: _sfc_main$g },
  emits: ['add-stage', 'add-transition'],
  computed: {
    panelPosition() {
      return isRTL() ? 'top-right' : 'top-left';
    },
  },
};

const _hoisted_1$2 = {
  id: "controls-panel-title",
  class: "visually-hidden"
};
const _hoisted_2$2 = ["aria-label", "title"];
const _hoisted_3$2 = ["aria-label", "title"];

function render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Panel = resolveComponent("Panel");

  return (openBlock(), createBlock(_component_Panel, {
    position: $options.panelPosition,
    role: "list",
    "aria-labelledby": "controls-panel-title",
    class: "d-flex gap-2 p-2"
  }, {
    default: withCtx(() => [
      createBaseVNode("span", _hoisted_1$2, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_CONTROLS')), 1 /* TEXT */),
      createBaseVNode("button", {
        class: "toolbar-button btn btn-primary d-flex align-items-center gap-1",
        "aria-label": _ctx.translate('COM_WORKFLOW_GRAPH_ADD_STAGE'),
        title: `${_ctx.translate('COM_WORKFLOW_GRAPH_ADD_STAGE')} (Alt+N)`,
        onClick: _cache[0] || (_cache[0] = $event => (_ctx.$emit('add-stage'))),
        onKeydown: [
          _cache[1] || (_cache[1] = withKeys(withModifiers($event => (_ctx.$emit('add-stage')), ["prevent"]), ["enter"])),
          _cache[2] || (_cache[2] = withKeys(withModifiers($event => (_ctx.$emit('add-stage')), ["prevent"]), ["space"]))
        ]
      }, [
        _cache[6] || (_cache[6] = createBaseVNode("span", {
          class: "icon icon-plus",
          "aria-hidden": "true"
        }, null, -1 /* CACHED */)),
        createTextVNode(" " + toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_ADD_STAGE')), 1 /* TEXT */)
      ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_2$2),
      createBaseVNode("button", {
        class: "toolbar-button btn btn-info d-flex align-items-center gap-1",
        "aria-label": _ctx.translate('COM_WORKFLOW_GRAPH_ADD_TRANSITION'),
        title: `${_ctx.translate('COM_WORKFLOW_GRAPH_ADD_TRANSITION')} (Alt+M)`,
        onClick: _cache[3] || (_cache[3] = $event => (_ctx.$emit('add-transition'))),
        onKeydown: [
          _cache[4] || (_cache[4] = withKeys(withModifiers($event => (_ctx.$emit('add-transition')), ["prevent"]), ["enter"])),
          _cache[5] || (_cache[5] = withKeys(withModifiers($event => (_ctx.$emit('add-transition')), ["prevent"]), ["space"]))
        ]
      }, [
        _cache[7] || (_cache[7] = createBaseVNode("span", {
          class: "icon icon-plus",
          "aria-hidden": "true"
        }, null, -1 /* CACHED */)),
        createTextVNode(" " + toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_ADD_TRANSITION')), 1 /* TEXT */)
      ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_3$2)
    ]),
    _: 1 /* STABLE */
  }, 8 /* PROPS */, ["position"]))
}

script$2.render = render$1;
script$2.__file = "media_source/com_workflow/src/components/canvas/ControlsPanel.vue";

/**
 * Announce a message via ARIA live region.
 * @param {HTMLElement} liveRegionElement
 * @param {string} message
 */
function announce(liveRegionElement, message) {
  if (!liveRegionElement || !message) return;
  liveRegionElement.textContent = '';
  setTimeout(() => {
    liveRegionElement.textContent = message;
  }, 10);
}

/**
 * Find and cycle focus among elements with a selector.
 * @param {string} selector - The selector for the elements to focus
 * @param {boolean} reverse - Whether to cycle focus in reverse order
 */
function cycleFocus(selector, reverse) {
  if (reverse === void 0) {
    reverse = false;
  }
  const elements = Array.from(document.querySelectorAll(selector));
  if (!elements.length) return;
  const currentIndex = elements.indexOf(document.activeElement);
  let nextIndex;
  if (reverse) {
    nextIndex = currentIndex <= 0 ? elements.length - 1 : currentIndex - 1;
  } else {
    nextIndex = currentIndex >= elements.length - 1 ? 0 : currentIndex + 1;
  }
  elements[nextIndex].focus();
}

/**
 * Cycle between defined focus modes (e.g., stages → transitions → toolbar → actions).
 * @param {string[]} focusModes - Array of focus mode strings.
 * @param {Ref<string>} currentModeRef - Vue ref holding the current mode.
 * @param {HTMLElement} liveRegionElement - ARIA live region for screen reader feedback.
 */
function cycleMode(focusModes, currentModeRef, liveRegionElement) {
  const currentIndex = focusModes.indexOf(currentModeRef.value);
  const nextIndex = (currentIndex + 1) % focusModes.length;
  currentModeRef.value = focusModes[nextIndex];
}

/**
 * Handle focus and keyboard events for dialog iframes.
 * This function sets focus to the first input or body of the iframe,
 * and adds an Escape key listener to close the dialog.
 *
 * @param {HTMLIFrameElement} iframe - The iframe element to handle.
 *
 */
function handleDialogIframeLoad(iframe) {
  try {
    iframe.focus();
    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
    if (iframeDoc) {
      const firstInput = iframeDoc.querySelector('input:not([type="hidden"]), select, textarea');
      if (firstInput) {
        firstInput.focus();
      } else {
        iframeDoc.body.focus();
      }
      iframeDoc.addEventListener('keydown', e => {
        if (e.code === 'Escape') {
          e.preventDefault();
          const parentDialog = document.querySelector('joomla-dialog dialog[open]');
          if (parentDialog && parentDialog.close) {
            parentDialog.close();
          }
        }
      });
    }
  } catch (error) {
    iframe.focus();
  }
}

/**
 * Handle dialog close event.
 * @param previouslyFocusedElement - The element that was focused before the dialog opened
 * @param store - The Vuex store instance
 */
function handleDialogClose(previouslyFocusedElement, store) {
  if (previouslyFocusedElement.value) {
    previouslyFocusedElement.value.focus();
    previouslyFocusedElement.value = null;
  }
  store.dispatch('loadWorkflow', store.getters.workflowId);
}

/**
 * Handle Escape keydown event on dialog.
 * @param e - The keyboard event
 */
function handleDialogKeydown(e) {
  if (e.code === 'Escape') {
    e.preventDefault();
    const dialog = e.currentTarget;
    if (dialog && dialog.close) {
      dialog.close();
    }
  }
}

/**
 * Setup focus handlers for dialog iframes.
 * This function will focus the dialog and handle iframe loading and closing.
 *
 * @param {Ref<HTMLElement>} previouslyFocusedElement - Ref to store the previously focused element.
 * @param {Object} store - Vuex store instance.
 */
function setupDialogFocusHandlers(previouslyFocusedElement, store) {
  setTimeout(() => {
    const dialog = document.querySelector('joomla-dialog dialog[open]');
    if (dialog) {
      dialog.focus();
      const iframe = dialog.querySelector('iframe');
      if (iframe) {
        iframe.addEventListener('load', () => {
          handleDialogIframeLoad(iframe);
        });
      }
      dialog.addEventListener('close', () => {
        handleDialogClose(previouslyFocusedElement, store);
      });
      dialog.addEventListener('keydown', handleDialogKeydown);
    }
  }, 100);
}

/**
 * Calculate and return positioned stage nodes in a grid layout.
 * @param {Array<Object>} stages - Array of stage objects.
 * @param {Object} options - Grid layout options (gapX, gapY, paddingX, paddingY).
 * @returns {Array<Object>} Array of positioned node configs.
 */
function generatePositionedNodes(stages, options) {
  if (options === void 0) {
    options = {};
  }
  const _options = options,
    _options$gapX = _options.gapX,
    gapX = _options$gapX === void 0 ? 400 : _options$gapX,
    _options$gapY = _options.gapY,
    gapY = _options$gapY === void 0 ? 300 : _options$gapY,
    _options$paddingX = _options.paddingX,
    paddingX = _options$paddingX === void 0 ? 100 : _options$paddingX,
    _options$paddingY = _options.paddingY,
    paddingY = _options$paddingY === void 0 ? 100 : _options$paddingY;
  const columns = Math.min(4, Math.ceil(Math.sqrt((stages == null ? void 0 : stages.length) || 0)) + 1);
  return stages.map((stage, index) => {
    const col = index % columns;
    const row = Math.floor(index / columns);
    const position = (stage == null ? void 0 : stage.position) || {
      x: col * gapX + paddingX,
      y: row * gapY + paddingY
    };
    return {
      id: String(stage.id),
      type: 'stage',
      position,
      data: {
        stage: _extends({}, stage, {
          color: getColorForStage(stage)
        }),
        isSelected: false,
        onSelect: () => {},
        onEdit: () => {},
        onDelete: () => {}
      },
      draggable: true
    };
  });
}

/**
 * Create special static nodes like "from_any" node.
 * @param {String} id - The ID of the special node
 * @param {Object} position - The position of the special node
 * @param {String} color - The color of the special node
 * @param {String} label - The label of the special node
 * @param {Function} onSelect - The function to call when the special node is selected
 * @param {Boolean} draggable - Whether the special node is draggable
 */
function createSpecialNode(id, position, color, label, onSelect, draggable) {
  if (onSelect === void 0) {
    onSelect = () => {};
  }
  return {
    id,
    type: 'stage',
    position,
    data: {
      stage: {
        id,
        title: label,
        published: true,
        color
      },
      isSpecial: true,
      onSelect
    },
    draggable
  };
}

/**
 * Generate styled edges based on transition data.
 * @param {Array<Object>} transitions - List of transitions.
 * @param {Object} options - Optional configuration - contains selected transition id.
 * @returns {Array<Object>} Styled edge definitions.
 */
function generateStyledEdges(transitions, options) {
  if (options === void 0) {
    options = {};
  }
  const _options = options,
    _options$selectedId = _options.selectedId,
    selectedId = _options$selectedId === void 0 ? null : _options$selectedId;

  // Group transitions by source-target pair
  const edgeGroups = {};
  transitions.forEach(transition => {
    const sourceId = transition.from_stage_id === -1 ? 'from_any' : String(transition.from_stage_id);
    const targetId = String(transition.to_stage_id);
    const key = sourceId + "__" + targetId;
    if (!edgeGroups[key]) edgeGroups[key] = [];
    edgeGroups[key].push(transition);
  });

  // Assign offsetIndex for each edge in a group
  const edgeOffsetMap = new Map();
  Object.entries(edgeGroups).forEach(_ref => {
    _ref[0];
      let group = _ref[1];
    group.forEach((transition, idx) => {
      edgeOffsetMap.set(transition.id, idx - (group.length - 1) / 2);
    });
  });
  return transitions.map(transition => {
    const sourceId = transition.from_stage_id === -1 ? 'from_any' : String(transition.from_stage_id);
    const targetId = String(transition.to_stage_id);
    const isSelected = transition.id === selectedId;
    const isBiDirectional = transitions.some(t => t.from_stage_id === transition.to_stage_id && t.to_stage_id === transition.from_stage_id);

    // Offset index for multiple edges between same source-target
    let offsetIndex = edgeOffsetMap.get(transition.id) || 0;

    // If bidirectional, add a small extra offset to separate further
    if (isBiDirectional) {
      offsetIndex += transition.from_stage_id > transition.to_stage_id ? 1 : -1;
    }
    const edgeColor = getEdgeColor(transition, isSelected);
    const strokeWidth = isSelected ? 5 : 3;
    return {
      id: String(transition.id),
      source: sourceId,
      target: targetId,
      type: 'custom',
      animated: isSelected,
      style: {
        stroke: edgeColor,
        strokeWidth,
        strokeDasharray: transition.published ? undefined : '5,5',
        zIndex: isSelected ? 1000 : 1
      },
      markerEnd: {
        type: 'arrow',
        width: 10,
        height: 10,
        color: edgeColor
      },
      data: _extends({}, transition, {
        isSelected,
        isBiDirectional,
        offsetIndex,
        onEdit: () => {},
        onDelete: () => {}
      })
    };
  });
}

/**
 * Attach global keyboard listeners for workflow canvas.
 * @param {Object} options
 * @param {Function} addStage - Function to add a new stage
 * @param {Function} addTransition - Function to add a new transition
 * @param {Function} editItem - Function to edit an item
 * @param {Function} deleteItem - Function to delete an item
 * @param {Function} setSaveStatus - Function to set the save status of positions
 * @param {Function} updateSaveMessage - Function to update the save message
 * @param {Function} saveNodePosition - Function to save the node position
 * @param {Function} clearSelection - Function to clear the selection
 * @param {Function} zoomIn - Function to zoom in
 * @param {Function} zoomOut - Function to zoom out
 * @param {Function} fitView - Function to fit the view
 * @param {Ref<Object>} viewport - Ref to the viewport object
 * @param {Object} state - { selectedStage, selectedTransition, isTransitionMode, liveRegion }
 * @param {Object} store - Vuex store instance
 */
function setupGlobalShortcuts(_ref) {
  let addStage = _ref.addStage,
    addTransition = _ref.addTransition,
    editItem = _ref.editItem,
    deleteItem = _ref.deleteItem,
    setSaveStatus = _ref.setSaveStatus,
    updateSaveMessage = _ref.updateSaveMessage,
    saveNodePosition = _ref.saveNodePosition,
    clearSelection = _ref.clearSelection,
    zoomIn = _ref.zoomIn,
    zoomOut = _ref.zoomOut,
    fitView = _ref.fitView,
    viewport = _ref.viewport,
    state = _ref.state,
    store = _ref.store;
  function handleKey(e) {
    const iframe = document.querySelector('joomla-dialog dialog[open]');
    if (iframe) {
      if (e.code === 'Escape') {
        e.preventDefault();
        iframe.close();
        return;
      }
      return;
    }
    const groupSelectors = {
      buttons: 'button, button:not([tabindex="-1"])',
      stages: '.stage-node',
      transitions: '.edge-label',
      toolbar: '.toolbar-button',
      actions: '.action-button',
      links: 'a[href], a[href]:not([tabindex="-1"])'
    };
    function moveNode(stageId, direction, fast) {
      const el = document.querySelector(".stage-node[data-stage-id='" + stageId + "']");
      if (!el || !store) return;
      const moveBy = 20 ;
      const stageIndex = store.getters.stages.findIndex(s => s.id === parseInt(stageId, 10));
      if (stageIndex === -1) return;
      const currentPosition = store.getters.stages[stageIndex].position || {
        x: 0,
        y: 0
      };
      if (!currentPosition) return;
      let x = currentPosition.x,
        y = currentPosition.y;
      switch (direction) {
        case 'ArrowUp':
          y -= moveBy;
          break;
        case 'ArrowDown':
          y += moveBy;
          break;
        case 'ArrowLeft':
          x -= moveBy;
          break;
        case 'ArrowRight':
          x += moveBy;
          break;
        default:
          return;
      }
      store.dispatch('updateStagePosition', {
        id: stageId,
        x,
        y
      });
      setSaveStatus('unsaved');
      updateSaveMessage();
      saveNodePosition();
    }
    switch (true) {
      /* ---------- Add Stage / Transition ---------- */
      case e.altKey && e.code === 'KeyN':
        e.preventDefault();
        addStage();
        announce(state.liveRegion, 'Add stage');
        break;
      case e.altKey && e.code === 'KeyM':
        e.preventDefault();
        addTransition();
        announce(state.liveRegion, 'Add transition');
        break;

      /* ---------- Edit / Delete ---------- */
      case e.code === 'KeyE':
        e.preventDefault();
        editItem();
        break;
      case e.code === 'Delete' || e.code === 'Backspace':
        e.preventDefault();
        deleteItem();
        break;
      case e.code === 'Escape':
        e.preventDefault();
        clearSelection();
        break;

      /* ---------- Zoom / View ---------- */
      case e.code === 'Equal':
        // + / =
        e.preventDefault();
        zoomIn();
        break;
      case e.code === 'Minus':
        // - / _
        e.preventDefault();
        zoomOut();
        break;
      case e.code === 'KeyF':
        e.preventDefault();
        fitView({
          padding: 0.5,
          duration: 300
        });
        break;

      /* ---------- Focus Mode Cycling ---------- */
      case e.code === 'Tab':
        {
          e.preventDefault();
          cycleMode(['buttons', 'stages', 'transitions', 'toolbar', 'actions', 'links'], state.currentFocusMode, state.liveRegion);
          const tabSelector = groupSelectors[state.currentFocusMode.value];
          if (tabSelector) {
            const first = document.querySelector(tabSelector);
            if (first) first.focus();
          }
          break;
        }

      /* ---------- Arrow Navigation ---------- */
      case ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.code):
        e.preventDefault();
        if (state.selectedStage.value) {
          if (e.shiftKey) {
            moveNode(state.selectedStage.value.toString(), e.code);
          } else {
            const buttonSelector = ".stage-node[data-stage-id='" + state.selectedStage.value + "'] button[tabindex=\"0\"]";
            if (buttonSelector) cycleFocus(buttonSelector, 0);
          }
        } else if (state.selectedTransition.value) {
          const buttonSelector = ".edge-label[data-edge-id='" + state.selectedTransition.value + "'] button[tabindex=\"0\"]";
          if (buttonSelector) cycleFocus(buttonSelector, 0);
        } else if (e.shiftKey) {
          const panStep = 20;
          switch (e.code) {
            case 'ArrowUp':
              viewport.value.y += panStep;
              break;
            case 'ArrowDown':
              viewport.value.y -= panStep;
              break;
            case 'ArrowLeft':
              viewport.value.x += panStep;
              break;
            case 'ArrowRight':
              viewport.value.x -= panStep;
              break;
          }
        } else {
          const reverse = ['ArrowLeft', 'ArrowUp'].includes(e.code);
          const selector = groupSelectors[state.currentFocusMode.value];
          if (selector) cycleFocus(selector, reverse);
        }
        break;
    }
  }
  document.addEventListener('keydown', handleKey);
  return () => {
    document.removeEventListener('keydown', handleKey);
  };
}

/**
 * VueFlow Accessibility Fixer
 * Handles accessibility issues that cannot be fixed with CSS alone
 */

class AccessibilityFixer {
  constructor() {
    this.observer = null;
    this.processedElements = new WeakSet();
  }

  /**
   * Initialize the accessibility fixer
   */
  init() {
    // Initial fix
    this.fixVueFlowAccessibility();

    // Set up mutation observer to handle dynamically added elements
    this.setupMutationObserver();

    // Fix elements when VueFlow updates
    this.setupVueFlowObserver();
  }

  /**
   * Fix all VueFlow accessibility issues
   */
  fixVueFlowAccessibility() {
    // Fix SVG elements
    this.fixSVGElements();

    // Fix tabbable groups
    this.fixTabbableGroups();

    // Fix graphics-document elements
    this.fixGraphicsDocuments();

    // Fix button accessible names
    this.fixButtonAccessibleNames();

    // Fix duplicate SVG element IDs
    this.fixDuplicateSVGIds();
  }

  /**
   * Hide all SVG elements from screen readers
   */
  fixSVGElements() {
    const svgSelectors = ['.vue-flow svg', '.vue-flow [role="graphics-document"]', '.vue-flow__background svg', '.vue-flow__minimap svg', '.vue-flow__edge svg', '.vue-flow__nodes svg', '.vue-flow__edges svg', 'svg[role="graphics-document"]', 'g[role="group"] svg', 'g[role="group"] [role="graphics-document"]'];
    svgSelectors.forEach(selector => {
      const elements = document.querySelectorAll(selector);
      elements.forEach(element => {
        if (!this.processedElements.has(element)) {
          this.hideSVGFromScreenReaders(element);
          this.processedElements.add(element);
        }
      });
    });
  }

  /**
   * Hide individual SVG element from screen readers
   */
  hideSVGFromScreenReaders(element) {
    // Only add aria-hidden to elements where it's valid
    if (!this.isInvalidForAriaHidden(element)) {
      element.setAttribute('aria-hidden', 'true');
    }

    // Only set role="presentation" on elements where it's valid
    if (!this.isInvalidForPresentationRole(element)) {
      element.setAttribute('role', 'presentation');
    }

    // Remove ARIA attributes that shouldn't be on decorative elements
    if (!this.isInvalidForAriaAttributes(element)) {
      element.removeAttribute('aria-label');
      element.removeAttribute('aria-labelledby');
      element.removeAttribute('aria-describedby');
    }

    // Also hide all children
    const children = element.querySelectorAll('*');
    children.forEach(child => {
      // Only add aria-hidden to child elements where it's valid
      if (!this.isInvalidForAriaHidden(child)) {
        child.setAttribute('aria-hidden', 'true');
      }

      // Only set role="presentation" on child elements where it's valid
      if (!this.isInvalidForPresentationRole(child)) {
        child.setAttribute('role', 'presentation');
      }

      // Remove ARIA attributes from children where appropriate
      if (!this.isInvalidForAriaAttributes(child)) {
        child.removeAttribute('aria-label');
        child.removeAttribute('aria-labelledby');
        child.removeAttribute('aria-describedby');
      }
    });
  }

  /**
   * Check if role="presentation" is invalid for this element
   */
  isInvalidForPresentationRole(element) {
    var _element$tagName;
    const invalidTags = ['title', 'desc', 'metadata'];
    return invalidTags.includes((_element$tagName = element.tagName) == null ? void 0 : _element$tagName.toLowerCase());
  }

  /**
   * Check if aria-hidden is invalid for this element
   */
  isInvalidForAriaHidden(element) {
    var _element$tagName2;
    // aria-hidden is invalid on title elements when they have role="none"
    const tagName = (_element$tagName2 = element.tagName) == null ? void 0 : _element$tagName2.toLowerCase();
    if (tagName === 'title' && element.getAttribute('role') === 'none') {
      return true;
    }
    // aria-hidden should not be used on title, desc, metadata elements in general
    const invalidTags = ['title', 'desc', 'metadata'];
    return invalidTags.includes(tagName);
  }

  /**
   * Check if ARIA attributes should be removed from this element
   */
  isInvalidForAriaAttributes(element) {
    var _element$tagName3;
    // Don't remove ARIA attributes from elements that might legitimately use them
    const tagName = (_element$tagName3 = element.tagName) == null ? void 0 : _element$tagName3.toLowerCase();
    const protectedTags = ['title', 'desc', 'metadata'];
    return protectedTags.includes(tagName);
  }

  /**
   * Fix tabbable group elements
   */
  fixTabbableGroups() {
    const groups = document.querySelectorAll('.vue-flow [role="group"][tabindex], [role="group"][tabindex]');
    groups.forEach(group => {
      if (!this.processedElements.has(group)) {
        // Remove tabindex from non-interactive groups
        const hasInteractiveChildren = group.querySelector('button, [role="button"], [role="menuitem"], input, select, textarea, a[href]');
        if (!hasInteractiveChildren) {
          group.removeAttribute('tabindex');
          group.style.pointerEvents = 'none';
        } else {
          // If it has interactive children, make the group non-focusable but keep children interactive
          group.removeAttribute('tabindex');
          group.style.userSelect = 'none';
          group.style.webkitUserSelect = 'none';
          group.style.mozUserSelect = 'none';
        }
        this.processedElements.add(group);
      }
    });
  }

  /**
   * Fix graphics-document elements
   */
  fixGraphicsDocuments() {
    const graphicsElements = document.querySelectorAll('[role="graphics-document"]');
    graphicsElements.forEach(element => {
      if (!this.processedElements.has(element)) {
        this.hideSVGFromScreenReaders(element);
        this.processedElements.add(element);
      }
    });
  }

  /**
   * Fix button accessible names to match visible text
   */
  fixButtonAccessibleNames() {
    const buttons = document.querySelectorAll('.stage-node[role="button"], .edge-label[role="button"]');
    buttons.forEach(button => {
      if (!this.processedElements.has(button)) {
        // Remove any conflicting aria-label that doesn't match visible text
        const currentLabel = button.getAttribute('aria-label');
        const visibleText = this.getVisibleText(button);
        if (currentLabel && visibleText && !visibleText.includes(currentLabel) && !currentLabel.includes(visibleText)) {
          // If aria-label doesn't match visible text, remove it to let the browser use visible text
          button.removeAttribute('aria-label');
        }
        this.processedElements.add(button);
      }
    });
  }

  /**
   * Fix duplicate SVG element IDs
   */
  fixDuplicateSVGIds() {
    const seenIds = new Set();
    const elementsWithIds = document.querySelectorAll('svg [id], .vue-flow [id]');
    elementsWithIds.forEach(element => {
      const id = element.id;
      if (id && seenIds.has(id)) {
        // Generate a unique ID
        const uniqueId = this.generateUniqueId(id, seenIds);
        element.id = uniqueId;
        seenIds.add(uniqueId);
      } else if (id) {
        seenIds.add(id);
      }
    });
  }

  /**
   * Generate a unique ID based on the original ID
   */
  generateUniqueId(originalId, seenIds) {
    let counter = 1;
    let newId = originalId + "-" + counter;
    while (seenIds.has(newId)) {
      counter++;
      newId = originalId + "-" + counter;
    }
    return newId;
  }

  /**
   * Get the main visible text content of an element
   */
  getVisibleText(element) {
    // For stage nodes, get the title
    const titleElement = element.querySelector('.card-title');
    if (titleElement) {
      return titleElement.textContent.trim();
    }

    // For edge labels, get the header text
    const headerElement = element.querySelector('header .card-title');
    if (headerElement) {
      return headerElement.textContent.trim();
    }

    // Fallback to any text content
    return element.textContent.trim().split('\n')[0].trim();
  }

  /**
   * Setup mutation observer to handle dynamically added elements
   */
  setupMutationObserver() {
    this.observer = new MutationObserver(mutations => {
      let shouldProcess = false;
      mutations.forEach(mutation => {
        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
          mutation.addedNodes.forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE) {
              // Check if the added node is a VueFlow element or contains VueFlow elements
              if (node.matches && (node.matches('.vue-flow *') || node.matches('svg') || node.matches('[role="graphics-document"]') || node.matches('[role="group"]') || node.querySelector('.vue-flow *, svg, [role="graphics-document"], [role="group"]'))) {
                shouldProcess = true;
              }
            }
          });
        }
      });
      if (shouldProcess) {
        // Debounce the processing
        clearTimeout(this.processTimeout);
        this.processTimeout = setTimeout(() => {
          this.fixVueFlowAccessibility();
        }, 100);
      }
    });
    this.observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  }

  /**
   * Setup VueFlow-specific observer for when the flow updates
   */
  setupVueFlowObserver() {
    // Also listen for VueFlow specific events if available
    const vueFlowElement = document.querySelector('.vue-flow');
    if (vueFlowElement) {
      // Set up additional observer for VueFlow container changes
      const vueFlowObserver = new MutationObserver(() => {
        clearTimeout(this.vueFlowTimeout);
        this.vueFlowTimeout = setTimeout(() => {
          this.fixVueFlowAccessibility();
        }, 200);
      });
      vueFlowObserver.observe(vueFlowElement, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['role', 'tabindex', 'aria-label', 'aria-hidden']
      });
    }
  }

  /**
   * Cleanup the fixer
   */
  destroy() {
    if (this.observer) {
      this.observer.disconnect();
    }
    clearTimeout(this.processTimeout);
    clearTimeout(this.vueFlowTimeout);
  }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    const fixer = new AccessibilityFixer();
    fixer.init();

    // Make it globally available for cleanup
    window.workflowAccessibilityFixer = fixer;
  });
} else {
  const fixer = new AccessibilityFixer();
  fixer.init();
  window.workflowAccessibilityFixer = fixer;
}

/**
 * Send a notification
 * @param {String} message
 * @param {{}} options
 *
 */
function _notify(message, options) {
  let timer;
  if (options.type === 'message') {
    timer = 3000;
  }
  Joomla.renderMessages({
    [options.type]: [Joomla.Text._(message)]
  }, undefined, true, timer);
}
const notifications = {
  /* Send a success notification */
  success: (message, options) => {
    _notify(message, _extends({
      type: 'message',
      // @todo rename it to success
      dismiss: true
    }, options));
  },
  /* Send an error notification */
  error: (message, options) => {
    _notify(message, _extends({
      type: 'error',
      // @todo rename it to danger
      dismiss: true
    }, options));
  },
  /* Send a general notification */
  notify: (message, options) => {
    _notify(message, _extends({
      type: 'message',
      dismiss: true
    }, options));
  },
  /* Ask the user a question */
  ask: message => window.confirm(message)
};

var script$1 = {
  name: 'WorkflowCanvas',
  components: {
    VueFlow: _sfc_main$1$1,
    Background: _sfc_main$2,
    MiniMap: _sfc_main,
    CustomControls: script$3,
    ControlsPanel: script$2,
  },
  props: {
    nodeTypes: {
      type: Object,
      default: () => ({ stage: script$5 }),
    },
    edgeTypes: {
      type: Object,
      default: () => ({ custom: script$4 }),
    },
    saveStatus: { type: Object, required: true },
    setSaveStatus: { type: Function, required: true },
  },
  setup(props, { emit }) {
    const store = useStore();
    const {
      fitView, zoomIn, zoomOut, viewport, setViewport, onViewportChange,
    } = useVueFlow();

    const isTransitionMode = ref(false);
    const selectedStage = ref(null);
    const selectedTransition = ref(null);
    const liveRegion = ref(null);
    const saveStatus = ref('upToDate');
    const currentFocusMode = ref('links');
    const previouslyFocusedElement = ref(null);
    const accessibilityFixer = ref(null);

    const showMiniMap = ref(true);
    const workflow = computed(() => store.getters.workflow || {});
    const stages = computed(() => store.getters.stages || []);
    const transitions = computed(() => store.getters.transitions || []);
    const loading = computed(() => store.getters.loading);
    const error = computed(() => store.getters.error);
    const workflowId = computed(() => store.getters.workflowId);

    function translate(key) {
      return Joomla.Text._(key);
    }

    function sprintf(key, ...args) {
      const base = Joomla.Text._(key);
      let i = 0;
      return base.replace(/%((%)|s|d)/g, (m) => {
        let val = args[i];

        if (m === '%d') {
          val = parseFloat(val);
          if (Number.isNaN(val)) {
            val = 0;
          }
        }
        i += 1;
        return val;
      });
    }

    function openModal(type, id = null, params = {}) {
      previouslyFocusedElement.value = document.activeElement;
      const extension = Joomla.getOptions('com_workflow', {})?.extension || '';
      const baseUrl = `index.php?option=com_workflow&view=${type}&workflow_id=${workflowId.value}&extension=${extension}&layout=modal&tmpl=component`;
      const baseUrlwithId = id ? `${baseUrl}&id=${id}` : baseUrl;
      const extraQuery = Object.entries(params)
        .map(([key, val]) => `${encodeURIComponent(key)}=${encodeURIComponent(val)}`)
        .join('&');
      const src = extraQuery
        ? `${baseUrlwithId}&${extraQuery}`
        : baseUrlwithId;

      const textHeader = id
        ? translate(`COM_WORKFLOW_GRAPH_EDIT_${type.toUpperCase()}`)
        : translate(`COM_WORKFLOW_GRAPH_ADD_${type.toUpperCase()}`);

      const dialog = new JoomlaDialog({
        popupType: 'iframe',
        textHeader,
        src,
      });

      const msgListener = (message) => {
        try {
          if (message.source === (dialog.getBodyContent().contentWindow || window)) {
            const type = message.data && message.data.messageType;
            if (type === 'joomla:content-select') {
              dialog.close();
            }
          }
        } catch (e) {
          // Ignore cross-origin errors
        }
      };
      window.addEventListener('message', msgListener);

      dialog.addEventListener('joomla-dialog:close', () => {
        window.removeEventListener('message', msgListener);
        dialog.destroy();
      });

      dialog.show();
      setupDialogFocusHandlers(previouslyFocusedElement, store);
    }

    function canEdit(id, type = 'stage') {
      if (type === 'stage') {
        const stage = stages.value.find((s) => s.id === parseInt(id, 10));
        return stage?.permissions?.edit;
      }
      if (type === 'transition') {
        const transition = transitions.value.find((t) => t.id === parseInt(id, 10));
        return transition?.permissions?.edit;
      }
      return false;
    }

    function canDelete(id, type = 'stage') {
      if (type === 'stage') {
        const stage = stages.value.find((s) => s.id === parseInt(id, 10));
        return stage?.permissions?.delete;
      }
      if (type === 'transition') {
        const transition = transitions.value.find((t) => t.id === parseInt(id, 10));
        return transition?.permissions?.delete;
      }
      return false;
    }

    function selectStage(id) {
      isTransitionMode.value = false;
      selectedStage.value = parseInt(id, 10);
      selectedTransition.value = null;
      announce(liveRegion.value, sprintf('COM_WORKFLOW_GRAPH_STAGE_SELECTED', stages?.value?.find((s) => s.id === parseInt(id, 10))?.title || id));
    }

    function selectTransition(id) {
      isTransitionMode.value = true;
      selectedTransition.value = parseInt(id, 10);
      selectedStage.value = null;
      announce(liveRegion.value, sprintf('COM_WORKFLOW_GRAPH_TRANSITION_SELECTED', transitions?.value?.find((t) => t.id === parseInt(id, 10))?.title || id));
    }

    function editStage(id) {
      if (!canEdit(id, 'stage')) {
        return;
      }
      openModal('stage', id);
    }

    function editTransition(id) {
      if (!canEdit(id, 'transition')) {
        return;
      }
      openModal('transition', id);
    }

    function clearSelection() {
      selectedStage.value = null;
      selectedTransition.value = null;
      isTransitionMode.value = false;
      announce(liveRegion.value, translate('COM_WORKFLOW_GRAPH_SELECTION_CLEARED'));
    }

    function deleteStage(id) {
      if (!canDelete(id, 'stage')) {
        return;
      }
      store.dispatch('deleteStage', { id, workflowId: workflowId.value });
      selectedStage.value = null;
    }

    function deleteTransition(id) {
      if (!canDelete(id, 'transition')) {
        return;
      }
      store.dispatch('deleteTransition', { id, workflowId: workflowId.value });
      selectedTransition.value = null;
    }

    function handleDeleteConfirm(type, id) {
      if (type === 'stage') deleteStage(id.toString());
      else deleteTransition(id.toString());
    }

    function showDeleteModal(type, id) {
      if ((!canDelete(id, 'stage') && type === 'stage') || (!canDelete(id, 'transition') && type === 'transition')) {
        return;
      }
      const title = translate(type === 'stage'
        ? 'COM_WORKFLOW_GRAPH_TRASH_STAGE'
        : 'COM_WORKFLOW_GRAPH_TRASH_TRANSITION');

      const message = translate(type === 'stage'
        ? sprintf('COM_WORKFLOW_GRAPH_TRASH_STAGE_CONFIRM', stages?.value?.find((s) => s.id === parseInt(id, 10))?.title || id)
        : sprintf('COM_WORKFLOW_GRAPH_TRASH_TRANSITION_CONFIRM', transitions?.value?.find((t) => t.id === parseInt(id, 10))?.title || id));

      JoomlaDialog.confirm(message, title).then((result) => {
        if (result) {
          handleDeleteConfirm(type, id);
        }
      });
    }

    function addStage() {
      if (!workflow?.value?.canCreate) {
        return;
      }
      openModal('stage');
      announce(liveRegion.value, translate('COM_WORKFLOW_GRAPH_ADD_STAGE_DIALOG_OPENED'));
    }

    function addTransition() {
      if (!workflow?.value?.canCreate) {
        return;
      }
      openModal('transition');
      announce(liveRegion.value, translate('COM_WORKFLOW_GRAPH_ADD_TRANSITION_DIALOG_OPENED'));
    }

    function handleConnect({ source, target }) {
      if (!workflow?.value?.canCreate) {
        return;
      }
      if (source && target) {
        openModal('transition', null, { from_stage_id: source, to_stage_id: target });
        announce(liveRegion.value, sprintf('COM_WORKFLOW_GRAPH_CREATING_TRANSITION', (stages?.value?.find((s) => s.id === parseInt(source, 10))?.title || source), (stages?.value?.find((s) => s.id === parseInt(target, 10))?.title || target)));
      }
    }

    function selectEdge({ edge }) {
      selectTransition(edge?.id);
    }

    function updateSaveMessage() {
      const el = document.getElementById('save-status');
      if (!el) return;
      if (saveStatus.value === 'unsaved') {
        el.classList.add('text-warning');
        el.textContent = translate('COM_WORKFLOW_GRAPH_UNSAVED_CHANGES');
      } else {
        el.classList.remove('text-warning');
        el.textContent = translate('COM_WORKFLOW_GRAPH_UP_TO_DATE');
      }
    }

    const saveNodePosition = debounce(async () => {
      const response = await store.dispatch('updateStagePositionAjax');
      if (response) {
        saveStatus.value = 'upToDate';
        updateSaveMessage();
        announce(liveRegion.value, translate('COM_WORKFLOW_GRAPH_STAGE_POSITIONS_UPDATED'));
      } else {
        notifications.error(translate('COM_WORKFLOW_GRAPH_ERROR_FAILED_TO_UPDATE_STAGE_POSITIONS'));
      }
    }, 3000);

    async function handleNodeDragStop({ node }) {
      if (!node || !node.id || node.id === 'from_any') return;
      const position = store.getters.stages.find((s) => s.id === parseInt(node.id, 10))?.position;
      const nodePosition = node.computedPosition || position || { x: 0, y: 0 };
      const { x, y } = nodePosition;
      saveStatus.value = 'unsaved';
      updateSaveMessage();
      await store.dispatch('updateStagePosition', { id: node.id, x, y });
      saveNodePosition();
    }

    const positionedNodes = computed(() => {
      const nodes = generatePositionedNodes(stages.value);
      const special = createSpecialNode('from_any', { x: 600, y: -200 }, 'purple', translate('COM_WORKFLOW_GRAPH_FROM_ANY'), selectStage, false);
      return [...nodes.map((n) => ({
        ...n,
        data: {
          ...n.data,
          isSelected: selectedStage.value === parseInt(n.id, 10),
          onSelect: () => selectStage(n.id),
          onEscape: () => clearSelection(),
          onEdit: () => editStage(n.id),
          onDelete: () => showDeleteModal('stage', n.id),
        },
      })), special];
    });

    const styledEdges = computed(() => generateStyledEdges(transitions.value, {
      selectedId: selectedTransition.value,
    }).map((edge) => ({
      ...edge,
      data: {
        ...edge.data,
        onSelect: () => selectTransition(edge.id),
        onEscape: () => clearSelection(),
        onDelete: () => showDeleteModal('transition', edge.id),
        onEdit: () => editTransition(edge.id),
      },
    })));
    onMounted(() => {
      // Initialize accessibility fixer
      accessibilityFixer.value = new AccessibilityFixer();
      accessibilityFixer.value.init();

      const detach = setupGlobalShortcuts({
        addStage,
        addTransition,
        editItem: () => {
          if (selectedStage.value) editStage(selectedStage.value);
          else if (selectedTransition.value) editTransition(selectedTransition.value);
        },
        deleteItem: () => {
          if (selectedStage.value) showDeleteModal('stage', selectedStage.value);
          else if (selectedTransition.value) showDeleteModal('transition', selectedTransition.value);
        },
        clearSelection,
        zoomIn,
        zoomOut,
        fitView,
        viewport,
        state: {
          selectedStage,
          selectedTransition,
          isTransitionMode,
          currentFocusMode,
          liveRegion: liveRegion.value,
        },
        setSaveStatus: (val) => { saveStatus.value = val; },
        updateSaveMessage,
        saveNodePosition,
        store,
      });
      onUnmounted(() => {
        detach();
        if (accessibilityFixer.value) {
          accessibilityFixer.value.destroy();
        }
      });
    });

    let isRestoringViewport = false;
    watch([loading, error], () => {
      setTimeout(() => {
        if (loading.value || error.value) return;
        const { panX, panY, zoom } = store.getters.canvas ?? {};

        if (
          typeof panX === 'number' && !Number.isNaN(panX)
          && typeof panY === 'number' && !Number.isNaN(panY)
          && typeof zoom === 'number' && !Number.isNaN(zoom)
        ) {
          isRestoringViewport = true;
          Promise.resolve()
            .then(() => setViewport({ x: panX, y: panY, zoom }))
            .finally(() => {
              isRestoringViewport = false;
            });
        } else {
          fitView({ padding: 0.4, duration: 300 });
        }
      }, 0);
    }, { immediate: true });

    onViewportChange(({ x, y, zoom }) => {
      if (isRestoringViewport) {
        return;
      }

      if ([x, y, zoom].some((v) => typeof v !== 'number' || Number.isNaN(v))) {
        return;
      }
      store.dispatch('updateCanvasViewport', { panX: x, panY: y, zoom });
    });

    return {
      loading,
      error,
      showMiniMap,
      workflow,
      stages,
      positionedNodes,
      styledEdges,
      liveRegion,
      handleConnect,
      selectEdge,
      handleDeleteConfirm,
      addStage,
      addTransition,
      clearSelection,
      handleNodeDragStop,
    };
  },
};

const _hoisted_1$1 = {
  class: "w-100 h-100 position-relative",
  role: "application",
  "aria-label": "Workflow Graph"
};
const _hoisted_2$1 = {
  id: "workflow-controls",
  "aria-label": "Canvas Controls",
  class: "workflow-controls-section"
};
const _hoisted_3$1 = { class: "visually-hidden" };
const _hoisted_4$1 = ["title", "aria-pressed"];
const _hoisted_5$1 = {
  key: 0,
  class: "fa fa-close",
  "aria-hidden": "true"
};
const _hoisted_6 = {
  key: 1,
  class: "icon icon-expand-2",
  "aria-hidden": "true"
};
const _hoisted_7 = { class: "visually-hidden" };
const _hoisted_8 = {
  id: "workflow-stages",
  class: "visually-hidden"
};
const _hoisted_9 = ["id"];
const _hoisted_10 = { key: 0 };
const _hoisted_11 = {
  id: "workflow-transitions",
  class: "visually-hidden"
};
const _hoisted_12 = ["id"];
const _hoisted_13 = {
  key: 1,
  class: "d-flex justify-content-center align-items-center h-100",
  role: "status",
  "aria-live": "polite"
};
const _hoisted_14 = {
  class: "spinner-border",
  role: "status"
};
const _hoisted_15 = { class: "visually-hidden" };
const _hoisted_16 = { class: "ms-2" };
const _hoisted_17 = {
  ref: "liveRegion",
  "aria-live": "polite",
  role: "status",
  class: "visually-hidden",
  "aria-atomic": "true"
};

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Background = resolveComponent("Background");
  const _component_MiniMap = resolveComponent("MiniMap");
  const _component_CustomControls = resolveComponent("CustomControls");
  const _component_ControlsPanel = resolveComponent("ControlsPanel");
  const _component_VueFlow = resolveComponent("VueFlow");

  return (openBlock(), createElementBlock("div", _hoisted_1$1, [
    (!$setup.loading && !$setup.error)
      ? (openBlock(), createBlock(_component_VueFlow, {
          key: 0,
          class: "workflow-canvas",
          "max-zoom": "2.5",
          "min-zoom": ".3",
          edges: $setup.styledEdges,
          nodes: $setup.positionedNodes,
          "node-types": $props.nodeTypes,
          "edge-types": $props.edgeTypes,
          "nodes-connectable": $setup.workflow?.canCreate,
          "elements-selectable": true,
          "snap-to-grid": true,
          "snap-grid": [40, 40],
          "disable-keyboard-a11y": true,
          role: "application",
          "aria-label": _ctx.translate('COM_WORKFLOW_GRAPH_CANVAS_LABEL'),
          onConnect: $setup.handleConnect,
          onPaneClick: $setup.clearSelection,
          onEdgeClick: $setup.selectEdge,
          onNodeDragStop: $setup.handleNodeDragStop
        }, {
          default: withCtx(() => [
            createVNode(_component_Background, {
              "pattern-color": "var(--body-color)",
              gap: 16,
              "aria-label": _ctx.translate('COM_WORKFLOW_GRAPH_BACKGROUND'),
              title: _ctx.translate('COM_WORKFLOW_GRAPH_BACKGROUND')
            }, null, 8 /* PROPS */, ["aria-label", "title"]),
            createCommentVNode(" Controls Section "),
            createBaseVNode("section", _hoisted_2$1, [
              createBaseVNode("span", _hoisted_3$1, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_CONTROLS')), 1 /* TEXT */),
              createCommentVNode(" Minimap Toggle "),
              createBaseVNode("button", {
                id: "toggle-minimap",
                class: "toolbar-button custom-controls-button position-absolute z-20 ps-2 pe-2",
                tabindex: "0",
                style: normalizeStyle($setup.showMiniMap ? 'bottom: 130px; left: 175px;' : 'bottom: 10px; left: 10px;'),
                title: $setup.showMiniMap ? _ctx.translate('COM_WORKFLOW_GRAPH_MINIMAP_HIDE') : _ctx.translate('COM_WORKFLOW_GRAPH_MINIMAP_SHOW'),
                "aria-pressed": $setup.showMiniMap ? 'true' : 'false',
                onClick: _cache[0] || (_cache[0] = $event => ($setup.showMiniMap = !$setup.showMiniMap))
              }, [
                ($setup.showMiniMap)
                  ? (openBlock(), createElementBlock("span", _hoisted_5$1))
                  : (openBlock(), createElementBlock("span", _hoisted_6)),
                createBaseVNode("span", _hoisted_7, toDisplayString($setup.showMiniMap ? _ctx.translate('COM_WORKFLOW_GRAPH_MINIMAP_HIDE') : _ctx.translate('COM_WORKFLOW_GRAPH_MINIMAP_SHOW')), 1 /* TEXT */)
              ], 12 /* STYLE, PROPS */, _hoisted_4$1),
              ($setup.showMiniMap)
                ? (openBlock(), createBlock(_component_MiniMap, {
                    key: 0,
                    class: "z-10",
                    position: "bottom-left",
                    pannable: "",
                    zoomable: "",
                    "aria-label": _ctx.translate('COM_WORKFLOW_GRAPH_MINIMAP_LABEL'),
                    "node-color": (node) => node.data?.stage?.color || '#0d6efd',
                    "mask-color": 'rgba(255, 255, 255, .6)'
                  }, null, 8 /* PROPS */, ["aria-label", "node-color"]))
                : createCommentVNode("v-if", true),
              createVNode(_component_CustomControls, {
                "aria-label": _ctx.translate('COM_WORKFLOW_GRAPH_CONTROLS')
              }, null, 8 /* PROPS */, ["aria-label"]),
              ($setup.workflow?.canCreate)
                ? (openBlock(), createBlock(_component_ControlsPanel, {
                    key: 1,
                    class: "canvas-controls-panel",
                    onAddStage: $setup.addStage,
                    onAddTransition: $setup.addTransition
                  }, null, 8 /* PROPS */, ["onAddStage", "onAddTransition"]))
                : createCommentVNode("v-if", true)
            ]),
            createCommentVNode(" Workflow Content Sections "),
            createBaseVNode("section", _hoisted_8, [
              createBaseVNode("span", null, "(" + toDisplayString($setup.positionedNodes.length) + " " + toDisplayString($setup.positionedNodes.length === 1 ? _ctx.translate('COM_WORKFLOW_GRAPH_STAGE') : _ctx.translate('COM_WORKFLOW_GRAPH_STAGES')) + ")", 1 /* TEXT */),
              createBaseVNode("ul", null, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($setup.positionedNodes, (node) => {
                  return (openBlock(), createElementBlock("li", {
                    id: `stage-list-${node.id}`,
                    key: `stage-${node.id}`
                  }, [
                    createTextVNode(toDisplayString(_ctx.sprintf('COM_WORKFLOW_GRAPH_STAGE_REF', node.data.stage?.title)) + " - " + toDisplayString(node.data.stage.published ? _ctx.sprintf('COM_WORKFLOW_GRAPH_STAGE_STATUS_ENABLED', node.data.stage?.title) : _ctx.sprintf('COM_WORKFLOW_GRAPH_STAGE_STATUS_DISABLED', node.data.stage?.title)) + ". ", 1 /* TEXT */),
                    (node.data.stage.default)
                      ? (openBlock(), createElementBlock("span", _hoisted_10, "(" + toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_STAGE_DEFAULT')) + ")", 1 /* TEXT */))
                      : createCommentVNode("v-if", true),
                    createTextVNode(" " + toDisplayString(node.data.stage?.description ? _ctx.sprintf('COM_WORKFLOW_GRAPH_STAGE_DESCRIPTION', node.data.stage?.description) : ''), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_9))
                }), 128 /* KEYED_FRAGMENT */))
              ])
            ]),
            createBaseVNode("section", _hoisted_11, [
              createBaseVNode("span", null, "(" + toDisplayString($setup.styledEdges.length) + " " + toDisplayString($setup.styledEdges.length === 1 ? _ctx.translate('COM_WORKFLOW_GRAPH_TRANSITION') : _ctx.translate('COM_WORKFLOW_GRAPH_TRANSITIONS')) + ")", 1 /* TEXT */),
              createBaseVNode("ul", null, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($setup.styledEdges, (edge) => {
                  return (openBlock(), createElementBlock("li", {
                    id: `transition-list-${edge.id}`,
                    key: `transition-${edge.id}`
                  }, toDisplayString(_ctx.sprintf('COM_WORKFLOW_GRAPH_TRANSITION_REF', edge.data.title, $setup.stages.find((s) => s.id === parseInt(edge.source, 10))?.title || _ctx.translate('COM_WORKFLOW_GRAPH_FROM_ANY'), $setup.stages.find((s) => s.id === parseInt(edge.target, 10))?.title)) + " " + toDisplayString(edge.data.published ? _ctx.sprintf('COM_WORKFLOW_GRAPH_TRANSITION_STATUS_ENABLED', edge.data.title) : _ctx.sprintf('COM_WORKFLOW_GRAPH_TRANSITION_STATUS_DISABLED', edge.data.title)), 9 /* TEXT, PROPS */, _hoisted_12))
                }), 128 /* KEYED_FRAGMENT */))
              ])
            ])
          ]),
          _: 1 /* STABLE */
        }, 8 /* PROPS */, ["edges", "nodes", "node-types", "edge-types", "nodes-connectable", "aria-label", "onConnect", "onPaneClick", "onEdgeClick", "onNodeDragStop"]))
      : createCommentVNode("v-if", true),
    createCommentVNode(" Loading State "),
    ($setup.loading)
      ? (openBlock(), createElementBlock("div", _hoisted_13, [
          createBaseVNode("div", _hoisted_14, [
            createBaseVNode("span", _hoisted_15, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_LOADING')), 1 /* TEXT */)
          ]),
          createBaseVNode("span", _hoisted_16, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_LOADING')), 1 /* TEXT */)
        ]))
      : createCommentVNode("v-if", true),
    createCommentVNode(" Accessibility Live Region "),
    createBaseVNode("div", _hoisted_17, null, 512 /* NEED_PATCH */)
  ]))
}

script$1.render = render;
script$1.__file = "media_source/com_workflow/src/components/canvas/WorkflowCanvas.vue";

const _hoisted_1 = {
  id: "workflow-app",
  class: "d-flex flex-column flex-grow-1 min-vh-80",
  "aria-labelledby": "workflow-main-title"
};
const _hoisted_2 = {
  id: "workflow-header",
  class: "d-flex flex-column flex-shrink-0"
};
const _hoisted_3 = { class: "d-flex flex-grow-1 overflow-hidden" };
const _hoisted_4 = {
  id: "main-canvas",
  class: "flex-grow-1 position-relative",
  "aria-describedby": "canvas-description"
};
const _hoisted_5 = {
  id: "canvas-description",
  class: "visually-hidden"
};


const __default__ = {
  name: 'WorkflowGraphApp',
};


var script = /*@__PURE__*/Object.assign(__default__, {
  setup(__props) {

const store = useStore();
const saveStatus = ref('upToDate');
function setSaveStatus(val) {
  saveStatus.value = val;
}
const canvas = ref(null);

function handleCanvasFocus() {
  canvas.value?.focus();
}

onMounted(() => {
  const { workflowId: idFromOpts = null } = Joomla.getOptions('com_workflow', {});
  const idFromURL = parseInt(new URL(window.location.href).searchParams.get('id'), 10);
  const currentWorkflowId = idFromOpts || idFromURL;

  if (currentWorkflowId !== null && !Number.isNaN(currentWorkflowId)) {
    store.dispatch('loadWorkflow', currentWorkflowId);
  } else {
    throw new Error('COM_WORKFLOW_GRAPH_ERROR_INVALID_ID');
  }
});

return (_ctx, _cache) => {
  return (openBlock(), createElementBlock("main", _hoisted_1, [
    createBaseVNode("header", _hoisted_2, [
      createVNode(script$6, { "save-status": saveStatus.value }, null, 8 /* PROPS */, ["save-status"])
    ]),
    createBaseVNode("div", _hoisted_3, [
      createBaseVNode("section", _hoisted_4, [
        createBaseVNode("div", _hoisted_5, toDisplayString(_ctx.translate('COM_WORKFLOW_GRAPH_CANVAS_DESCRIPTION')), 1 /* TEXT */),
        createVNode(script$1, {
          ref_key: "canvas",
          ref: canvas,
          "save-status": saveStatus.value,
          "set-save-status": setSaveStatus,
          onFocusRequest: handleCanvasFocus
        }, null, 8 /* PROPS */, ["save-status"])
      ])
    ])
  ]))
}
}

});

script.__file = "media_source/com_workflow/src/components/App.vue";

/**
 * Simple Event Bus for cross-module communication
 * Used to communicate between Joomla buttons and Vue app
 */
var EventBus = new class EventBus {
  /**
   * Internal registry of events
   * @type {Object<string, Function[]>}
   */
  constructor() {
    this.events = {};
  }

  /**
   * Trigger a custom event with optional payload
   * @param {string} event - Event name
   * @param {*} [data=null] - Optional payload
   */
  fire(event, data) {
    if (data === void 0) {
      data = null;
    }
    (this.events[event] || []).forEach(fn => fn(data));
  }

  /**
   * Register a callback for an event
   * @param {string} event - Event name
   * @param {Function} callback - Function to invoke on event
   */
  listen(event, callback) {
    if (!this.events[event]) {
      this.events[event] = [];
    }
    this.events[event].push(callback);
  }

  /**
   * Remove a listener from an event
   * @param {string} event - Event name
   * @param {Function} callback - Function to remove
   */
  off(event, callback) {
    if (this.events[event]) {
      this.events[event] = this.events[event].filter(fn => fn !== callback);
    }
  }
}();

/**
 * Reactive base state for the workflow graph
 * Includes workflow ID, workflow, stages, loading, transitions, history, and canvas viewport
 */
var state = {
  workflowId: null,
  workflow: null,
  stages: [],
  transitions: [],
  loading: false,
  error: null,
  canvas: {
    zoom: null,
    panX: null,
    panY: null
  }
};

/**
 * Vuex Mutations for synchronously modifying workflow state
 */
var mutations = {
  SET_WORKFLOW_ID(state, id) {
    state.workflowId = id;
  },
  SET_WORKFLOW(state, workflow) {
    state.workflow = workflow;
  },
  SET_STAGES(state, stages) {
    state.stages = stages.map((stage, idx) => {
      var _stage$position, _stage$position2;
      return _extends({}, stage, {
        position: {
          x: typeof (stage == null || (_stage$position = stage.position) == null ? void 0 : _stage$position.x) === 'number' && !Number.isNaN(stage.position.x) ? stage.position.x : 100 + idx % 4 * 400,
          y: typeof (stage == null || (_stage$position2 = stage.position) == null ? void 0 : _stage$position2.y) === 'number' && !Number.isNaN(stage.position.y) ? stage.position.y : 100 + Math.floor(idx / 4) * 300
        }
      });
    });
  },
  SET_TRANSITIONS(state, transitions) {
    state.transitions = transitions;
  },
  SET_LOADING(state, loading) {
    state.loading = loading;
  },
  SET_ERROR(state, error) {
    state.error = error;
  },
  UPDATE_STAGE_POSITION(state, _ref) {
    let id = _ref.id,
      x = _ref.x,
      y = _ref.y;
    state.stages = state.stages.map(stage => {
      if (stage.id.toString() === id) {
        return _extends({}, stage, {
          position: {
            x,
            y
          }
        });
      }
      return stage;
    });
  },
  SET_CANVAS_VIEWPORT(state, _ref2) {
    let zoom = _ref2.zoom,
      panX = _ref2.panX,
      panY = _ref2.panY;
    state.canvas.zoom = zoom;
    state.canvas.panX = panX;
    state.canvas.panY = panY;
  }
};

/**
 * Handles API communication for the workflow graph.
 */
class WorkflowGraphApi {
  /**
   * Initializes the WorkflowGraphApi instance.
   *
   * @throws {TypeError} If required options are missing.
   */
  constructor() {
    const _Joomla$getOptions = Joomla.getOptions('com_workflow', {}),
      apiBaseUrl = _Joomla$getOptions.apiBaseUrl,
      extension = _Joomla$getOptions.extension;
    if (!apiBaseUrl || !extension) {
      throw new TypeError(Joomla.Text._('COM_WORKFLOW_GRAPH_API_NOT_SET'));
    }
    this.baseUrl = apiBaseUrl;
    this.extension = extension;
    this.csrfToken = Joomla.getOptions('csrf.token', null);
    if (!this.csrfToken) {
      throw new TypeError(Joomla.Text._('COM_WORKFLOW_GRAPH_ERROR_CSRF_TOKEN_NOT_SET'));
    }
  }

  /**
   * Makes a request using Joomla.request.
   *
   * @param {string} url - The endpoint relative to baseUrl.
   * @param {Object} [options={}] - Request config (method, data, headers).
   * @returns {Promise<any>} The parsed response or error.
   */
  async makeRequest(url, options) {
    if (options === void 0) {
      options = {};
    }
    const headers = options.headers || {};
    headers['X-Requested-With'] = 'XMLHttpRequest';
    options.headers = headers;
    options[this.csrfToken] = 1;
    return new Promise((resolve, reject) => {
      Joomla.request(_extends({
        url: "" + this.baseUrl + url + "&extension=" + this.extension
      }, options, {
        onSuccess: response => {
          const data = JSON.parse(response);
          resolve(data);
        },
        onError: xhr => {
          let message = 'COM_WORKFLOW_GRAPH_ERROR_UNKNOWN';
          try {
            const errorData = JSON.parse(xhr.responseText);
            message = errorData.data || errorData.message || message;
          } catch (e) {
            message = xhr.statusText || message;
          }
          notifications.error(message);
          reject(new Error(Joomla.Text._(message)));
        }
      }));
    });
  }

  /**
   * Fetches workflow data by ID.
   *
   * @param {number} id - Workflow ID.
   * @returns {Promise<Object|null>}
   */
  async getWorkflow(id) {
    return this.makeRequest("&task=graph.getWorkflow&workflow_id=" + id + "&format=json");
  }

  /**
   * Fetches stages for a given workflow.
   *
   * @param {number} workflowId - Workflow ID.
   * @returns {Promise<Object[]|null>}
   */
  async getStages(workflowId) {
    return this.makeRequest("&task=graph.getStages&workflow_id=" + workflowId + "&format=json");
  }

  /**
   * Fetches transitions for a given workflow.
   *
   * @param {number} workflowId - Workflow ID.
   * @returns {Promise<Object[]|null>}
   */
  async getTransitions(workflowId) {
    return this.makeRequest("&task=graph.getTransitions&workflow_id=" + workflowId + "&format=json");
  }

  /**
   * Deletes a stage from a workflow.
   *
   * @param {number} id - Stage ID.
   * @param {number} workflowId - Workflow ID.
   * @param {boolean} [stageDelete=0] - Optional flag to indicate if the stage should be deleted or just trashed.
   *
   * @returns {Promise<boolean>}
   */
  async deleteStage(id, workflowId, stageDelete) {
    if (stageDelete === void 0) {
      stageDelete = false;
    }
    try {
      const formData = new FormData();
      formData.append('cid[]', id);
      formData.append('workflow_id', workflowId);
      formData.append('type', 'stage');
      formData.append(this.csrfToken, '1');
      const response = await this.makeRequest("&task=" + (stageDelete ? 'graph.delete' : 'graph.trash') + "&workflow_id=" + workflowId + "&format=json", {
        method: 'POST',
        data: formData
      });
      if (response && response.success) {
        var _response$data;
        notifications.success((response == null || (_response$data = response.data) == null ? void 0 : _response$data.message) || (response == null ? void 0 : response.message));
      }
    } catch (error) {
      notifications.error(error.message);
      throw error;
    }
  }

  /**
   * Deletes a transition from a workflow.
   *
   * @param {number} id - Transition ID.
   * @param {number} workflowId - Workflow ID.
   * @param {boolean} [transitionDelete=false] - Optional flag to indicate if the transition should be deleted or just trashed.
   *
   * @returns {Promise<boolean>}
   */
  async deleteTransition(id, workflowId, transitionDelete) {
    if (transitionDelete === void 0) {
      transitionDelete = false;
    }
    try {
      const formData = new FormData();
      formData.append('cid[]', id);
      formData.append('workflow_id', workflowId);
      formData.append('type', 'transition');
      formData.append(this.csrfToken, '1');
      const response = await this.makeRequest("&task=" + (transitionDelete ? 'graph.delete' : 'graph.trash') + "&workflow_id=" + workflowId + "&format=json", {
        method: 'POST',
        data: formData
      });
      if (response && response.success) {
        var _response$data2;
        notifications.success((response == null || (_response$data2 = response.data) == null ? void 0 : _response$data2.message) || (response == null ? void 0 : response.message));
      }
    } catch (error) {
      notifications.error(error.message);
      throw error;
    }
  }

  /**
   * Updates the position of a stage.
   *
   * @param {number} workflowId - Workflow ID.
   * @param {Object} positions - Position objects {x, y} of updated stages.
   * @returns {Promise<Object|null>}
   */
  async updateStagePosition(workflowId, positions) {
    try {
      const formData = new FormData();
      formData.append('workflow_id', workflowId);
      formData.append(this.csrfToken, '1');
      if (positions === null || Object.keys(positions).length === 0) {
        return true;
      }
      Object.entries(positions).forEach(_ref => {
        let id = _ref[0],
          position = _ref[1];
        formData.append("positions[" + id + "][x]", position.x);
        formData.append("positions[" + id + "][y]", position.y);
      });
      const response = await this.makeRequest('&task=stages.updateStagesPosition&format=json', {
        method: 'POST',
        data: formData
      });
      return !!(response && response.success);
    } catch (error) {
      notifications.error(error.message);
      throw error;
    }
  }
}
var workflowGraphApi = new WorkflowGraphApi();

/**
 * Vuex Actions for asynchronous operations and workflows
 * Handles logic and commits to mutations
 */
var actions = {
  /**
   * Load a workflow by its ID, including stages and transitions.
   * @param commit
   * @param id - The ID of the workflow
   * @returns {Promise<{workflow: Object, stages: Array, transitions: Array}>}
   */
  async loadWorkflow(_ref, id) {
    let commit = _ref.commit;
    commit('SET_LOADING', true);
    commit('SET_ERROR', null);
    try {
      // Load workflow, stages, and transitions in parallel
      const _await$Promise$all = await Promise.all([await workflowGraphApi.getWorkflow(id), await workflowGraphApi.getStages(id), await workflowGraphApi.getTransitions(id)]),
        workflowRes = _await$Promise$all[0],
        stagesRes = _await$Promise$all[1],
        transitionsRes = _await$Promise$all[2];
      commit('SET_WORKFLOW_ID', id);
      commit('SET_WORKFLOW', workflowRes == null ? void 0 : workflowRes.data);
      commit('SET_STAGES', stagesRes == null ? void 0 : stagesRes.data);
      commit('SET_TRANSITIONS', transitionsRes == null ? void 0 : transitionsRes.data);
    } catch (error) {
      var _error$response;
      notifications.error((error == null || (_error$response = error.response) == null || (_error$response = _error$response.data) == null ? void 0 : _error$response.message) || (error == null ? void 0 : error.message) || 'COM_WORKFLOW_GRAPH_ERROR_UNKNOWN');
    } finally {
      commit('SET_LOADING', false);
    }
  },
  /**
   * Delete a stage from the workflow.
   * @param commit
   * @param dispatch
   * @param state
   * @param id - The ID of the stage to delete
   * @param workflowId - The ID of the workflow
   * @returns {Promise<void>}
   */
  async deleteStage(_ref2, _ref3) {
    let commit = _ref2.commit,
      dispatch = _ref2.dispatch,
      state = _ref2.state;
    let id = _ref3.id,
      workflowId = _ref3.workflowId;
    commit('SET_LOADING', true);
    commit('SET_ERROR', null);
    try {
      const transitions = state.transitions.filter(t => t.from_stage_id.toString() === id || t.to_stage_id.toString() === id);
      if (state.stages.length <= 1 || state.stages.find(s => s.id.toString() === id).default) {
        notifications.error('COM_WORKFLOW_GRAPH_ERROR_STAGE_DEFAULT_CANT_DELETED');
        return;
      }
      if (transitions.length > 0) {
        notifications.error('COM_WORKFLOW_GRAPH_ERROR_STAGE_HAS_TRANSITIONS');
        return;
      }
      const stageDelete = state.stages.find(s => s.id.toString() === id).published === -1;
      await workflowGraphApi.deleteStage(id, workflowId, stageDelete);
    } catch (error) {
      var _error$response2;
      notifications.error((error == null || (_error$response2 = error.response) == null || (_error$response2 = _error$response2.data) == null ? void 0 : _error$response2.message) || (error == null ? void 0 : error.message) || 'COM_WORKFLOW_GRAPH_TRASH_STAGE_FAILED');
    } finally {
      commit('SET_LOADING', false);
      await dispatch('loadWorkflow', workflowId);
    }
  },
  /**
   * Delete a transition from the workflow.
   * @param commit
   * @param dispatch
   * @param state
   * @param id - The ID of the transition to delete
   * @param workflowId - The ID of the workflow
   * @returns {Promise<void>}
   */
  async deleteTransition(_ref4, _ref5) {
    let commit = _ref4.commit,
      dispatch = _ref4.dispatch,
      state = _ref4.state;
    let id = _ref5.id,
      workflowId = _ref5.workflowId;
    commit('SET_LOADING', true);
    commit('SET_ERROR', null);
    try {
      const transitionDelete = state.transitions.find(t => t.id.toString() === id).published === -1;
      await workflowGraphApi.deleteTransition(id, workflowId, transitionDelete);
    } catch (error) {
      var _error$response3;
      notifications.error((error == null || (_error$response3 = error.response) == null || (_error$response3 = _error$response3.data) == null ? void 0 : _error$response3.message) || (error == null ? void 0 : error.message) || 'COM_WORKFLOW_GRAPH_TRASH_TRANSITION_FAILED');
    } finally {
      commit('SET_LOADING', false);
      await dispatch('loadWorkflow', workflowId);
    }
  },
  /**
   * Update the position of a stage in the workflow locally.
   * @param commit
   * @param id - The ID of the stage
   * @param x - The new x position of the stage
   * @param y - The new y position of the stage
   */
  updateStagePosition(_ref6, _ref7) {
    let commit = _ref6.commit;
    let id = _ref7.id,
      x = _ref7.x,
      y = _ref7.y;
    commit('UPDATE_STAGE_POSITION', {
      id,
      x,
      y
    });
  },
  /**
   * Update the position of a stage in the workflow via API in database.
   * @param commit
   * @param state
   * @returns {Promise<boolean>}
   */
  async updateStagePositionAjax(_ref8) {
    let commit = _ref8.commit,
      state = _ref8.state;
    const response = await workflowGraphApi.updateStagePosition(state.workflowId, state.stages.reduce((acc, stage) => {
      if (stage.position) {
        acc[stage.id] = {
          x: stage.position.x,
          y: stage.position.y
        };
      }
      return acc;
    }, {}));
    if (response) {
      commit('SET_ERROR', null);
      return true;
    }
    notifications.error('COM_WORKFLOW_GRAPH_UPDATE_STAGE_POSITION_FAILED');
    return false;
  },
  /**
   * Update the canvas viewport (zoom and pan) for the workflow graph.
   * @param commit
   * @param zoom - The zoom level
   * @param panX - The pan offset on the X axis
   * @param panY - The pan offset on the Y axis
   */
  updateCanvasViewport(_ref9, _ref0) {
    let commit = _ref9.commit;
    let zoom = _ref0.zoom,
      panX = _ref0.panX,
      panY = _ref0.panY;
    commit('SET_CANVAS_VIEWPORT', {
      zoom,
      panX,
      panY
    });
  }
};

/**
 * Vuex Getters for accessing state in components
 * Provides reusable computed-like access to store data
 */
var getters = {
  workflowId: state => state.workflowId,
  workflow: state => state.workflow,
  stages: state => state.stages,
  transitions: state => state.transitions,
  loading: state => state.loading,
  error: state => state.error,
  canvas: state => state.canvas
};

/**
 * Vuex plugin for persisting selected store data to localStorage
 * Typically used for preserving UI state across reloads
 */
function createPersistedState(_temp) {
  let _ref = _temp === void 0 ? {} : _temp,
    _ref$key = _ref.key,
    key = _ref$key === void 0 ? 'vuex' : _ref$key,
    _ref$paths = _ref.paths,
    paths = _ref$paths === void 0 ? [] : _ref$paths;
  return store => {
    try {
      const stored = localStorage.getItem(key);
      if (stored) {
        const parsed = JSON.parse(stored);
        paths.forEach(path => {
          if (parsed[path] !== undefined) {
            store.state[path] = parsed[path];
          }
        });
      }
      store.subscribe((mutation, state) => {
        const partial = {};
        paths.forEach(path => {
          partial[path] = state[path];
        });
        localStorage.setItem(key, JSON.stringify(partial));
      });
    } catch (err) {
      if (window.Joomla && window.Joomla.renderMessages) {
        window.Joomla.renderMessages({
          error: [err]
        });
      }
    }
  };
}

/**
 * Vuex Store for Workflow Graph
 * Handles state, mutations, actions, getters, and persistence of workflow graph data
 */
var store = createStore({
  state,
  mutations,
  actions,
  getters,
  plugins: [createPersistedState({
    key: 'workflow-graph-state',
    paths: ['workflowId', 'stages', 'transitions']
  })]
});

/**
 * Joomla Translation Plugin Wrapper
 * Provides global `translate` and `sprintf` methods to all Vue components
 */
const Translate = {
  /**
   * Translate a Joomla key
   * Falls back to key if translation is missing
   * @param {string} key
   * @returns {string}
   */
  translate: key => Joomla.Text._(key, key),
  /**
   * Format string using Joomla `sprintf`
   * @param {string} string
   * @param {...*} args
   * @returns {string}
   */
  sprintf: function sprintf(string) {
    for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
      args[_key - 1] = arguments[_key];
    }
    const base = Translate.translate(string);
    let i = 0;
    return base.replace(/%((%)|s|d)/g, m => {
      let val = args[i];
      if (m === '%d') {
        val = parseFloat(val);
        if (Number.isNaN(val)) {
          val = 0;
        }
      }
      i += 1;
      return val;
    });
  },
  /**
   * Vue plugin install method
   * Adds $translate and $sprintf globally
   * @param {App} Vue
   */
  install: Vue => Vue.mixin({
    methods: {
      translate(key) {
        return Translate.translate(key);
      },
      sprintf(key) {
        for (var _len2 = arguments.length, args = new Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
          args[_key2 - 1] = arguments[_key2];
        }
        return Translate.sprintf(key, ...args);
      }
    }
  })
};

// Register WorkflowGraph namespace
window.WorkflowGraph = window.WorkflowGraph || {};
// Register the WorkflowGraph event bus
window.WorkflowGraph.Event = EventBus;
document.addEventListener('DOMContentLoaded', () => {
  const mountElement = document.getElementById('workflow-graph-root');
  if (mountElement) {
    const app = createApp(script);
    app.use(store);
    app.use(Translate);
    app.mount(mountElement);
  } else {
    notifications.error('COM_WORKFLOW_GRAPH_API_NOT_SET');
  }
});
