# Architecture

Small package, four deliberate shapes.

## Why the user side is a morph, not a foreign key

Applications with several authenticatable models (users, admins) share one histories table. A
morph cannot cascade, so the GDPR purge lives in the trait instead: deleting a user (the real
deletion — soft deletes keep the account and its history) removes every archived hash. No
orphaned credential material outlives its account.

## Why the check is a loop of `Hash::check()`

bcrypt/argon salts differ per hash, so `WHERE hash = ?` is impossible — the candidate must be
verified against each stored hash individually. That loop is also the timing-safe path; the
package never caches the plaintext or a weakened digest to "speed it up".

## Why a store failure fails closed

A password change is a security operation. If history cannot be read, passing the candidate
silently voids the reuse guarantee at exactly the moment it matters; failing the validation
with a retriable message costs one attempt. The application can invert this
(`on_store_error => 'degrade'`) where availability wins — the choice is explicit, never
ambient.

## Why the store is a contract

`PasswordHistoryStore` is the seam: an existing table, a different schema, an external
credential service — implement four methods and the rule, trait and command follow. The
Eloquent default binds `singletonIf`, so an application binding always wins regardless of
provider order.

## What this package is not

Not a strength check (that is `laranail/password-tools`), not a breach check (that is
`laranail/validation`'s `uncompromised()`), and not a signup rule — with no user to have a
history, the rule passes by design.

---

[← Docs index](../README.md#documentation)
