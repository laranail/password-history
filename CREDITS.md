# Credits

## Prior art

- The archived `enekia` password-history stack (Simtabi's own code) established the shape —
  model, trait, observer, prune command — and is redesigned here, not ported: its PSR-4
  mismatches, unwired observer, and unregistered commands do not carry over.
- [`imanghafoori1/laravel-password-history`](https://github.com/imanghafoori1/laravel-password-history)
  and [`vanthao03596/laravel-password-history`](https://github.com/vanthao03596/laravel-password-history)
  were studied as the niche's existing packages. No code was taken from either; the pattern —
  a loop of `Hash::check()` over the last N hashes, since per-hash salts make a WHERE-equality
  impossible — is the domain's only correct shape.

## Dependencies

- None beyond `illuminate/*` and the laranail foundation (`laranail/package-tools`,
  `laranail/console`).
