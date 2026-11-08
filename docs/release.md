# Release

Pre-1.0 on the org's single-moving-tag model: one `v0.1.0` tag that moves with each verified
change; consumers on `^0.1` pick it up on `composer update`. `release.yml` builds the GitHub
release body from the CHANGELOG section when a tag is pushed. Graduation to real SemVer
follows the family convention when the surface stabilizes.

**Moving the tag by delete-then-push — the route wherever `git push --force` is unavailable — reverts its
release to a *draft*, and `gh release edit` leaves that flag as it finds it**, so `release.yml` passes
`--draft=false`. Without it the run goes green having republished nothing, and nothing else reports it: the
tag resolves, `composer` installs, and the tag-currency check passes, because none of them reads the release.
So read it after a move:

```bash
gh release view v0.1.0 --repo laranail/password-history --json isDraft,publishedAt
```

`tests/ReleaseWorkflowTest.php` fails if any workflow's `gh release edit` drops the flag.

---

[← Docs index](../README.md#documentation)
