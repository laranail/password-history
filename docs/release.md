# Release

Pre-1.0 on the org's single-moving-tag model: one `v0.1.0` tag that moves with each verified
change; consumers on `^0.1` pick it up on `composer update`. `release.yml` builds the GitHub
release body from the CHANGELOG section when a tag is pushed. Graduation to real SemVer
follows the family convention when the surface stabilizes.

---

[← Docs index](../README.md#documentation)
