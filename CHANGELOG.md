# Changelog

## [1.2.1](https://github.com/MilliPress/MilliBase/compare/v1.2.0...v1.2.1) (2026-03-12)


### Bug Fixes

* use strict false-check in decrypt_value return ([d299631](https://github.com/MilliPress/MilliBase/commit/d299631adb53aeada2e630d1e1fe9a43be03f417))

## [1.2.0](https://github.com/MilliPress/MilliBase/compare/v1.1.0...v1.2.0) (2026-03-11)


### Features

* **ui:** auto-open section panel when active toggle is switched on ([fee7b5a](https://github.com/MilliPress/MilliBase/commit/fee7b5a054c76bc612fce6d289b4d9a006813d27))


### Refactoring

* **ui:** remove status indicator dot, keep badge only ([601aad0](https://github.com/MilliPress/MilliBase/commit/601aad0f664d4ca74b708f5b7e07de8cc3f021a2))

## [1.1.0](https://github.com/MilliPress/MilliBase/compare/v1.0.2...v1.1.0) (2026-03-10)


### Features

* **schema:** add active-toggle support for sections ([3b59239](https://github.com/MilliPress/MilliBase/commit/3b5923941a91cec19f1b86b32ca13dfd14e78e77))
* **ui:** render active toggles in section headers ([cbe4d23](https://github.com/MilliPress/MilliBase/commit/cbe4d232347c1575d6b647eedf20d1814bdce46e))

## [1.0.2](https://github.com/MilliPress/MilliBase/compare/v1.0.1...v1.0.2) (2026-03-09)


### Bug Fixes

* **settings:** add conditional return type to get() ([9435d84](https://github.com/MilliPress/MilliBase/commit/9435d844216e9b8ea702c43b2f27b87e3a5c35d3))

## [1.0.1](https://github.com/MilliPress/MilliBase/compare/v1.0.0...v1.0.1) (2026-03-09)


### Refactoring

* **settings:** unify get() and get_all() into single get() method ([df821bb](https://github.com/MilliPress/MilliBase/commit/df821bb85d7ed60fcc93a631cc2ce164d9e3a6d0))

## 1.0.0 (2026-03-09)


### Features

* Add conditional field visibility (show/hide) support ([e201934](https://github.com/MilliPress/MilliBase/commit/e2019346306384011751f121c04aef05ebe37610))
* **ci:** compile build assets on push to main ([ebaa8b3](https://github.com/MilliPress/MilliBase/commit/ebaa8b38228a6ce8cdb2cfe573691c7ed9d50d44))
* **docs:** Add README with project overview and usage instructions ([1eb4419](https://github.com/MilliPress/MilliBase/commit/1eb4419f0d5f7d1973f3bf392b5f1dc7d85932bf))
* **ErrorDisplay:** add configurable troubleshooting link ([9527038](https://github.com/MilliPress/MilliBase/commit/95270382f2ce4a640e41be78698ce0f4191bf70e))
* Initial package scaffolding for millipress/millisettings ([f72087c](https://github.com/MilliPress/MilliBase/commit/f72087cf7b44cd4ff6e2e3b0dd5ea764c7cb9aeb))
* **schema:** add replace flag to skip section merging on tab override ([76cb47f](https://github.com/MilliPress/MilliBase/commit/76cb47f47bc1d7284679146728aa063996bd9a78))
* **schema:** key tabs by name and sections by id for override support ([7486229](https://github.com/MilliPress/MilliBase/commit/7486229e535e3801fa035c0bb99074d665999b5f))
* **SectionRenderer:** add status indicator, badge, and dynamic initial_open ([d23ca6d](https://github.com/MilliPress/MilliBase/commit/d23ca6d701f1744dea7de951c121771b31351320))
* **store:** inject host.domain into full settings retrievals ([bfe990a](https://github.com/MilliPress/MilliBase/commit/bfe990a74d43d2414d449262a8755851a29d31e1))
* support array action names, add REST nonce verification ([db06a43](https://github.com/MilliPress/MilliBase/commit/db06a43c6f9745d78e7537b849478f4b4159ac34))
* **ui:** Add support for intro text in tabs and sections ([179fbd2](https://github.com/MilliPress/MilliBase/commit/179fbd2c5eb367fd74462defa87c161fa5543b4f))
* **ui:** Enhance header styling and layout for improved user experience ([84d97fc](https://github.com/MilliPress/MilliBase/commit/84d97fcdf370e79327556d18b2b7ee65dcd04b5d))


### Bug Fixes

* **ci:** use RELEASE_TOKEN in release-please workflow ([400f4b7](https://github.com/MilliPress/MilliBase/commit/400f4b7158e9601a7cebfe097ae1579e77e3ffb6))
* **docs:** Update WordPress version requirement to 6.6 ([7c2b1ff](https://github.com/MilliPress/MilliBase/commit/7c2b1ffe7682b7285b2d955f4fcb51b02ff661fc))
* **phpcs:** align arrays, add missing docblock tags ([4931b89](https://github.com/MilliPress/MilliBase/commit/4931b89872d00ec6002c2e7ffb5fe253fa946de3))
* **release:** set manifest to 0.0.0 so next release becomes v1.0.0 ([1207df2](https://github.com/MilliPress/MilliBase/commit/1207df27582f12b9f6d78a2132039eaa995864a2))
* Replace non-existent warning icon with cautionFilled, add build output ([b61cec6](https://github.com/MilliPress/MilliBase/commit/b61cec6145bcf384e44e5a798eb5e4ef977eff4c))
* resolve all PHPCS, PHPStan, and test violations ([b0a1a0d](https://github.com/MilliPress/MilliBase/commit/b0a1a0dceb210ff7dd10f8eaffa395a893ce8fa2))
* **rest:** use Store as single source of truth for option_name ([7c6d3c4](https://github.com/MilliPress/MilliBase/commit/7c6d3c4690c2247e43c12e1570619199fac51048))
* **settings:** register immediately when init has already fired ([3b9f4de](https://github.com/MilliPress/MilliBase/commit/3b9f4de4d6e77f7c7c9a100a7ec00b8671bcac58))
* UnitField dropdown not switching to best-fit unit ([ed3e3b8](https://github.com/MilliPress/MilliBase/commit/ed3e3b863e6281073a0625b6fd07bb825f5cf609))


### Refactoring

* **admin:** replace basename config with {SLUG}_BASENAME constant ([93958df](https://github.com/MilliPress/MilliBase/commit/93958dffcf588691756688dee7fc4cd8514bfbde))
* **admin:** use tertiary Button for troubleshooting link ([2233ffa](https://github.com/MilliPress/MilliBase/commit/2233ffa80d575ad8837d671f391817f6e2b38f36))
* **config:** move troubleshooting from header to top-level config ([92fc94b](https://github.com/MilliPress/MilliBase/commit/92fc94b3dd87c19ec0c3554d377c659c3d236ea4))
* **hooks:** guard register_hooks() against non-WP environments ([2ec3fd3](https://github.com/MilliPress/MilliBase/commit/2ec3fd3cfeb4d1802627992448ccef6233f4ffac))
* Rename package to millibase, fix PHPStan level max, use plugin slug for hooks ([4f2b079](https://github.com/MilliPress/MilliBase/commit/4f2b0799e38ac3d7a7f217f005a3b6cf07d7be10))
* rename Store → Settings, Settings → Manager ([cb706ef](https://github.com/MilliPress/MilliBase/commit/cb706ef2b7d56714aabd63762814640269b5f8da))
* **rest:** rename status_callback to status.callback and add status.data ([e05766d](https://github.com/MilliPress/MilliBase/commit/e05766d07912fa8570f70bef532f5a667032cbfa))
* **rest:** replace resolved settings with constants in status endpoint ([f3b6ebe](https://github.com/MilliPress/MilliBase/commit/f3b6ebefb3a32855e40eb18726f188e6883608ec))
* **rest:** restore descriptive hook names for REST endpoints ([e23c6d9](https://github.com/MilliPress/MilliBase/commit/e23c6d9693b45c526a1ad957e184908e9b57f139))
* **schema:** rename initial_open to open and store_as to store ([0d9452c](https://github.com/MilliPress/MilliBase/commit/0d9452cf4d9186961d8043465cabe06b3bebe7dd))
* **schema:** rename store to save to avoid confusion with Store class ([d42fada](https://github.com/MilliPress/MilliBase/commit/d42fadab1aa25cf6959f1da9263b900ad09dbd0f))
* **settings:** auto-derive rest_namespace from slug ([9dc5a06](https://github.com/MilliPress/MilliBase/commit/9dc5a06899f649e1d53a7642b036a9e84ed8382d))
* **settings:** default option_name to slug ([623cf91](https://github.com/MilliPress/MilliBase/commit/623cf9107092e68bd6357cffaf540c1c69428eb1))
* **settings:** rename schema hook and auto-derive option_name ([b03fa89](https://github.com/MilliPress/MilliBase/commit/b03fa89867db6015aa2767c9b4382f4d31ed65a9))
* **store:** auto-register hooks in constructor ([291805d](https://github.com/MilliPress/MilliBase/commit/291805daafb3e044b48f3d5591aae911cfe8681e))
* **store:** require slug, derive option_name from it ([b1f9221](https://github.com/MilliPress/MilliBase/commit/b1f92215055d4c3fce17bd62b752c62a4f5e540c))
* **store:** use {slug}_settings_defaults for defaults filter ([38d7a34](https://github.com/MilliPress/MilliBase/commit/38d7a342b217da8b309178600ee55500e7d0d187))
* **ui:** Remove unused ProgressBar import from SettingsApp ([3128ecc](https://github.com/MilliPress/MilliBase/commit/3128eccb0f0d0d12a1e39fb4dfe8478ed7772b7a))
