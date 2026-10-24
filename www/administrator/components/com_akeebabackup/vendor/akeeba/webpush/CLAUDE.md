# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Akeeba Web Push is a PHP library implementing the W3C Web Push Protocol for Joomla 4+ components. It allows Joomla extension developers to send encrypted browser push notifications to users. It is **not** a standalone Joomla component — it's a reusable library that components include via Composer or manual file copy.

**Derived from** Louis Lagrange's WebPush library, modified to use only Joomla-bundled dependencies.

The integration guide for consumers of this library lives in `docs/index.md`.

## Build

**No test suite, linter, or CI pipeline exists** in this repository.

## Architecture

`src/ECC/` is a custom Elliptic Curve Cryptography implementation used for VAPID public key operations. It depends on `Brick\Math\BigInteger`, which is deliberately **not** declared in `composer.json` — it comes bundled with Joomla, so the library only works inside a Joomla application.

The Joomla MVC integration is trait-based: `WebPushControllerTrait` adds the subscribe/unsubscribe endpoints to a controller, `WebPushModelTrait` adds VAPID key management and notification sending to a model. Subscriptions are stored as JSON in Joomla's `#__user_profiles` table under the key `{component}.webPushSubscription`; VAPID keys are stored in the component's parameters.

### Cryptography

Changes to VAPID or encryption code have breaking implications — the crypto stack (ECC, VAPID JWT signing, AES-GCM encryption) is tightly coupled and must remain compatible with the Web Push standard.

## Important Constraints

- **Plugin namespace conflict**: Cannot be used unmodified in Joomla plugins; namespace must be changed to avoid version conflicts between extensions.
- **Payload limits**: Max 4,078 bytes; compatibility limit 3,052 bytes.
- **Joomla version compat**: Handles Joomla 4/5/6 database API differences and PHP 8.1–8.5 reflection changes with runtime checks.
- **Static VAPID cache**: VAPID keys are cached in a static property per component to avoid repeated DB queries.
