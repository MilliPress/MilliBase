# Changelog

## [2.6.4](https://github.com/MilliPress/MilliBase/compare/v2.6.3...v2.6.4) (2026-06-22)


### Features

* **fields:** generalize unit field storage base to any time unit ([eba0ad1](https://github.com/MilliPress/MilliBase/commit/eba0ad1a57732fa6cef14b71149805c85f252049))
* **settings:** preserve flagged fields across a full reset ([93f5c02](https://github.com/MilliPress/MilliBase/commit/93f5c02431666cfb635913acef06fa1b4862621a))

## [2.6.3](https://github.com/MilliPress/MilliBase/compare/v2.6.2...v2.6.3) (2026-06-22)


### Bug Fixes

* **admin:** preload /settings and /status via apiFetch middleware ([87f7eab](https://github.com/MilliPress/MilliBase/commit/87f7eab5a364d0a36f8a51f3858ffacb001a85e9))
* **admin:** preload /settings and /status via apiFetch middleware ([c113949](https://github.com/MilliPress/MilliBase/commit/c1139495d6afaf098e17978c5fb7dd70adad3294))

## [2.6.2](https://github.com/MilliPress/MilliBase/compare/v2.6.1...v2.6.2) (2026-06-09)


### Features

* **settings:** add get_raw() per-key accessor that bypasses the defaults-gate ([a126027](https://github.com/MilliPress/MilliBase/commit/a1260278de70afbe900cec2d03bed1806a9a6978))

## [2.6.1](https://github.com/MilliPress/MilliBase/compare/v2.6.0...v2.6.1) (2026-06-09)


### Features

* **components:** add shared InfoPopover to the registry ([222c7bd](https://github.com/MilliPress/MilliBase/commit/222c7bdc86a79b14a451f8dd34e3eb71d27b9815))
* **settings:** support lock condition on section active toggles ([f10fd3f](https://github.com/MilliPress/MilliBase/commit/f10fd3fec018714706cd17e1a5b6f91bbda182e2))


### Bug Fixes

* **settings:** make TabPanel respond to programmatic and URL tab changes ([0407573](https://github.com/MilliPress/MilliBase/commit/04075737ab5eddd36e67001211744f98ca5fe96e))


### Miscellaneous

* **deps:** bump npm-dependencies group (minors only) ([#51](https://github.com/MilliPress/MilliBase/issues/51)) ([1c232c4](https://github.com/MilliPress/MilliBase/commit/1c232c450a110157c52199ed93e6bf91f5e570b5))

## [2.6.0](https://github.com/MilliPress/MilliBase/compare/v2.5.0...v2.6.0) (2026-05-28)


### Features

* **admin:** footer slots accept [component =&gt; name] for React portal hydration ([333dfb7](https://github.com/MilliPress/MilliBase/commit/333dfb7acd5cb358722ffbc415d064cadd77c6af))
* **admin:** override admin footer text on the settings page ([b803256](https://github.com/MilliPress/MilliBase/commit/b8032561f879f11ded3dea09e9a6edfd8c80c329))
* **cli:** expose settings_group_for() for downstream subcommands ([1fdb7fa](https://github.com/MilliPress/MilliBase/commit/1fdb7fa4f6c8f2b57d44d9c2a3fe9a25c70ecbf7))
* **fields:** add KeyField with masked-as-placeholder rendering + clear button ([3687378](https://github.com/MilliPress/MilliBase/commit/36873788970d14d2881abf1e21618f253da08db1))
* **schema:** add lock conditional for fields ([c3ab20c](https://github.com/MilliPress/MilliBase/commit/c3ab20cb0dd49d5cadaf1d977e5e94c42c97f4db))
* **schema:** gate sections by per-section capability ([976ae05](https://github.com/MilliPress/MilliBase/commit/976ae05c4de69ba975ae4ea86333951d3f59f261))
* **settings:** add partial-mask reveal for type:'key' secrets ([90cc221](https://github.com/MilliPress/MilliBase/commit/90cc221c5a6b2f9f4261416f42f5cd08044d6e63))
* **settings:** honor reload directive in action responses ([66ff3d4](https://github.com/MilliPress/MilliBase/commit/66ff3d418a00e193281bd126eb4880d8131a6154))


### Bug Fixes

* **fields:** render placeholder in PasswordField ([fd1a7ff](https://github.com/MilliPress/MilliBase/commit/fd1a7ff449ebe0b5ff25fb89581a1829c9b435f4))
* **filters:** pass $is_network to settings_defaults and rest_status_response ([06dcc47](https://github.com/MilliPress/MilliBase/commit/06dcc4705b07c442b6276cc92866162ac556f4f8))
* **lint:** satisfy stricter wordpress-stubs param annotations ([d215bbb](https://github.com/MilliPress/MilliBase/commit/d215bbbe48fe7bfcd6f4012b732d5b1c9d579d04))
* **rest:** mask encrypted secrets in settings/status responses ([6105817](https://github.com/MilliPress/MilliBase/commit/6105817be54583f5872e70f1b39700a259bd284b))
* **ui:** cap modal width at 600px ([2efb935](https://github.com/MilliPress/MilliBase/commit/2efb935dd5d1e6d45d126f2bf61e47cd060249b0))


### Miscellaneous

* **docs:** bump unshipped [@since](https://github.com/since) tags to 2.6.0 ([23b9945](https://github.com/MilliPress/MilliBase/commit/23b994519dc7197ac8b04b990b063315fa1bd287))

## [2.5.0](https://github.com/MilliPress/MilliBase/compare/v2.4.2...v2.5.0) (2026-05-13)


### Features

* **abilities:** add WordPress Abilities API support ([#43](https://github.com/MilliPress/MilliBase/issues/43)) ([b24e5d1](https://github.com/MilliPress/MilliBase/commit/b24e5d1469be1e4369e781f7a288d4b1b3da4823))
* **admin-page:** add network admin menu, use return value for hook suffix ([c9a3411](https://github.com/MilliPress/MilliBase/commit/c9a3411c3b8ad6495aff73653b8f87eca9975822))
* **cli:** auto-merge Managers sharing cli.slug into one Settings\Group ([b365ed1](https://github.com/MilliPress/MilliBase/commit/b365ed1087b3ff342ddc74b49dac5f7e1cfd2371))
* **manager:** add base add_page() for secondary admin pages ([2530d71](https://github.com/MilliPress/MilliBase/commit/2530d71a662ba3fa87179d7470af62e86ab59dda))
* **migration:** declarative migration runner on Manager ([b749573](https://github.com/MilliPress/MilliBase/commit/b74957305ff78fc69689e4b5a97e767ec787263f))
* **rest:** namespaced settings endpoints, drop /wp/v2/settings exposure ([66c5957](https://github.com/MilliPress/MilliBase/commit/66c5957db3941e0a0651063b89a3a0abe45c628b))
* **settings:** add read_raw() escape hatch for migrations ([45a1627](https://github.com/MilliPress/MilliBase/commit/45a16278cc3b2a9f0e61cde41712af2b0c4b3118))
* **settings:** network mode for site-options storage backend ([83fc011](https://github.com/MilliPress/MilliBase/commit/83fc0118bec4dfaba5b077827738c036704c20c6))


### Bug Fixes

* **fields:** Rename month duration constant to support lowercase requirement ([2f20b78](https://github.com/MilliPress/MilliBase/commit/2f20b786559b77b25ee52aed0cb26cd06f377f5d))
* **header:** anchor admin notices via wp-header-end ([dfcfef4](https://github.com/MilliPress/MilliBase/commit/dfcfef421c15cea89bf1bb1b664c74993d50b7fa))
* **settings,rest:** network-mode polish for resolve_domain and __reset ([fcce754](https://github.com/MilliPress/MilliBase/commit/fcce7549c2239cd8aef34c45d31d51f4470a7629))
* **settings:** branch set() on network mode ([a46b359](https://github.com/MilliPress/MilliBase/commit/a46b35954cb3877b20e9b3dae1ec6a735e7d9fde))
* **settings:** include blog path in subdirectory multisite filenames ([85aba71](https://github.com/MilliPress/MilliBase/commit/85aba710f1c0827e3be641e26276a06febf081f6))
* **settings:** resolve config file domain per operation ([76a0795](https://github.com/MilliPress/MilliBase/commit/76a0795edaf98056540c7827c71ce8ffe2d9be22))


### Refactoring

* **admin-page:** drop network_admin config in favor of network ([906e08f](https://github.com/MilliPress/MilliBase/commit/906e08f8f53ba625001aa0aa48ede4463b363159))
* **settings:** remove undocumented host pseudo-module from retrievals ([023af70](https://github.com/MilliPress/MilliBase/commit/023af702aa82207b533b600af43ee8b2390108a2))

## [2.4.2](https://github.com/MilliPress/MilliBase/compare/v2.4.1...v2.4.2) (2026-05-08)


### Bug Fixes

* **settings:** decrypt enc_* values loaded from the config file ([0d79d24](https://github.com/MilliPress/MilliBase/commit/0d79d24af1d4ec35880e864230028883a7c89f8d))


### Refactoring

* **schema:** drop unused `encrypted` field property ([b4bd86e](https://github.com/MilliPress/MilliBase/commit/b4bd86e36ce1b574b542acd56055b7ca6d88e349))

## [2.4.1](https://github.com/MilliPress/MilliBase/compare/v2.4.0...v2.4.1) (2026-05-03)


### Bug Fixes

* **fields:** render markdown-style links in help text ([#40](https://github.com/MilliPress/MilliBase/issues/40)) ([68765bf](https://github.com/MilliPress/MilliBase/commit/68765bf6b906cd5e060fcc82cb0c46063200933b))

## [2.4.0](https://github.com/MilliPress/MilliBase/compare/v2.3.1...v2.4.0) (2026-05-01)


### Features

* **schema:** chain action steps via array on button fields ([#38](https://github.com/MilliPress/MilliBase/issues/38)) ([3a46cb3](https://github.com/MilliPress/MilliBase/commit/3a46cb3b9eba94672fef593123207c50177e571b))

## [2.3.1](https://github.com/MilliPress/MilliBase/compare/v2.3.0...v2.3.1) (2026-05-01)


### Features

* **fields:** support `help` description text on field registration ([1ff2872](https://github.com/MilliPress/MilliBase/commit/1ff287262fb95e6778ff2a26fb65628abd4f8e85))


### Bug Fixes

* **controllers:** tolerate cross-prefix Settings in REST and CLI controllers ([032447e](https://github.com/MilliPress/MilliBase/commit/032447ea648c2be44340e2cab3d7688fc79b2424))


### Miscellaneous

* release 2.3.1 ([c802ab4](https://github.com/MilliPress/MilliBase/commit/c802ab439dbe4c43442d4f2c93c30091d69036b7))

## [2.3.0](https://github.com/MilliPress/MilliBase/compare/v2.2.1...v2.3.0) (2026-04-30)


### Features

* **schema:** sanitize settings payload before persistence ([9cf91fa](https://github.com/MilliPress/MilliBase/commit/9cf91faefde7162ebe096e03de6067e1bbdc1827))


### Bug Fixes

* **manager:** tolerate cross-prefix Settings during dual-active window ([f2d2e3e](https://github.com/MilliPress/MilliBase/commit/f2d2e3e115d5bbae3c339bed984ca00a1ef0478f))
* **settings:** split initial-load and action loading into distinct states ([#34](https://github.com/MilliPress/MilliBase/issues/34)) ([ffac56d](https://github.com/MilliPress/MilliBase/commit/ffac56d97270ea472e630d5e33ffa44e68f5c421))
* **snackbar:** position past WP admin sidebar instead of behind it ([0f61471](https://github.com/MilliPress/MilliBase/commit/0f61471ecbb602e1f45e2d85284409ead0902836))

## [2.2.1](https://github.com/MilliPress/MilliBase/compare/v2.2.0...v2.2.1) (2026-04-29)


### Bug Fixes

* **schema:** allow null in REST schema when default is null ([#32](https://github.com/MilliPress/MilliBase/issues/32)) ([f38bc56](https://github.com/MilliPress/MilliBase/commit/f38bc56835ea537860f8942475388d067090be21))
* **settings:** show recovery banner instead of crashing on null option ([2e93eda](https://github.com/MilliPress/MilliBase/commit/2e93edaff1b8aa499690c39a63e1342f955649cd))


### Refactoring

* **banner:** extract reusable Banner component ([350b488](https://github.com/MilliPress/MilliBase/commit/350b488bb7028a22f2cbb68cd22d3160673ae0e5))

## [2.2.0](https://github.com/MilliPress/MilliBase/compare/v2.1.1...v2.2.0) (2026-04-29)


### Features

* **schema:** add button field type with confirm-modal ([0c02229](https://github.com/MilliPress/MilliBase/commit/0c02229f442f85eb7a1c46077597b381804300d2))
* **schema:** expose status namespace to show/hide conditions ([a3853ff](https://github.com/MilliPress/MilliBase/commit/a3853ff59b5091eb36dda6c804c33bf70a6b8b51))

## [2.1.1](https://github.com/MilliPress/MilliBase/compare/v2.1.0...v2.1.1) (2026-04-24)


### Bug Fixes

* **settings:** Remove unnecessary margin from tab content ([4874a33](https://github.com/MilliPress/MilliBase/commit/4874a33a31adc78189ad348576df60e56de844b7))

## [2.1.0](https://github.com/MilliPress/MilliBase/compare/v2.0.0...v2.1.0) (2026-04-22)


### Features

* expose useSettings and useSnackbar hooks on window.MilliBase.hooks ([4b0f4ab](https://github.com/MilliPress/MilliBase/commit/4b0f4abfdc4e50ce17f85e822279aa40ff26e3ef))


### Bug Fixes

* add __nextHasNoMarginBottom props and remove deprecated unit prop ([c752f20](https://github.com/MilliPress/MilliBase/commit/c752f20b5953f83438c25400487746938055448a))
* hide tab bar scrollbar and scope centering to desktop ([177169e](https://github.com/MilliPress/MilliBase/commit/177169e5b36919317c6ccdf6c3ec8f182e32d018))
* memoize settings context and stabilize polling effect ([e6df09b](https://github.com/MilliPress/MilliBase/commit/e6df09bde617c227656c8bb110d3be48136036a9))
* stabilize SettingsProvider callback identities with useCallback ([f350f61](https://github.com/MilliPress/MilliBase/commit/f350f6147d16abad0734dedc65b1cc8ef21403a3))

## [2.0.0](https://github.com/MilliPress/MilliBase/compare/v1.2.4...v2.0.0) (2026-03-31)


### ⚠ BREAKING CHANGES

* Manager constructor now accepts (string $slug, \Closure $config, ?Settings $settings) instead of a single array.

### Features

* add accordion behavior and section groups ([4380b7e](https://github.com/MilliPress/MilliBase/commit/4380b7e9303f2aacca47ef0568340e2a32b8c638))
* add WP-CLI settings commands via CliController ([a03e119](https://github.com/MilliPress/MilliBase/commit/a03e119b88081d993bf924d9db765e5056b3d797))
* closure-based Manager constructor for deferred config resolution ([cacf19d](https://github.com/MilliPress/MilliBase/commit/cacf19d540cc47b6dfd24a80f6ca76444a8646d6))
* **settings:** fire per-key actions when settings change ([bc8c9a7](https://github.com/MilliPress/MilliBase/commit/bc8c9a743671e9149fc179aed0fe2f8b9fc87135))
* **settings:** Sync active tab with URL hash for better navigation ([9e4a6a0](https://github.com/MilliPress/MilliBase/commit/9e4a6a0bb4db18dc46219400ed3c65dff505d248))
* **ui:** Add condition support for header menu items ([942de9b](https://github.com/MilliPress/MilliBase/commit/942de9b8a947818c9b53965431dbf2eb0f4ec8f8))
* **ui:** Add sticky positioning to tabs for improved navigation experience ([71219a0](https://github.com/MilliPress/MilliBase/commit/71219a0b10094f4cfe01902c8ac00d560e120166))
* **ui:** Mobile-friendly header, desktop-only sticky tabs, and full icon set ([c10bbff](https://github.com/MilliPress/MilliBase/commit/c10bbff458e15d3eaa4b0d3e2cb14f8062b89444))
* **ui:** sticky header and smart scroll-to-reveal tabs navigation ([12fe7e8](https://github.com/MilliPress/MilliBase/commit/12fe7e8e47322726d4c2c9c7165d6264072d5332))


### Bug Fixes

* **ci:** use RELEASE_TOKEN for build-assets push to protected branch ([9168620](https://github.com/MilliPress/MilliBase/commit/9168620d2cfff1fae20a458f23db95030cc246da))
* PHP 7.4 compat and extract encrypted-key helper ([a69b2fc](https://github.com/MilliPress/MilliBase/commit/a69b2fc243ddb4f26a4bc12ac2910c1e108a7a5a))
* resolve PHPCS errors and suppress false-positive warnings ([d95f041](https://github.com/MilliPress/MilliBase/commit/d95f041f372ca7523423d336c71e4ab45cd79e07))
* **settings:** Ensure config file cleanup occurs on option deletion ([819c0ac](https://github.com/MilliPress/MilliBase/commit/819c0acaf67dbda893e6eb5ecb3e9862cc32e737))
* **ui:** Prevent modals from disrupting sticky positioning by adjusting body overflow ([051081f](https://github.com/MilliPress/MilliBase/commit/051081fcf938b73c782c0a9c407fd25130f95e5f))


### Refactoring

* move controllers to CLI and REST namespaces ([5eed702](https://github.com/MilliPress/MilliBase/commit/5eed702198225432675dd953528512abede8d454))
* rename FieldTypes to Fields namespace ([76b0edc](https://github.com/MilliPress/MilliBase/commit/76b0edcdb3bed7bdbfa3a889d3aa8a3cdd90d92b))
* **ui:** simplify field label/tooltip pattern ([de5cad7](https://github.com/MilliPress/MilliBase/commit/de5cad71f7ac28188b22461b2e3067ceaaad3e9a))

## [1.2.4](https://github.com/MilliPress/MilliBase/compare/v1.2.3...v1.2.4) (2026-03-16)


### Bug Fixes

* **assets:** inline fallback for builds outside the web root ([639295c](https://github.com/MilliPress/MilliBase/commit/639295c8774032f15cc94eefad4e2f527f62ab70))

## [1.2.3](https://github.com/MilliPress/MilliBase/compare/v1.2.2...v1.2.3) (2026-03-16)


### Bug Fixes

* defer boot() to init hook to avoid textdomain deprecation notice ([d08219e](https://github.com/MilliPress/MilliBase/commit/d08219e43c59dfeac457eba31b0fd89dce58c761))
* defer boot() to init hook to avoid textdomain deprecation notice ([5ad70ca](https://github.com/MilliPress/MilliBase/commit/5ad70cae4a22bdac17115d2ab797a5a6e9bd6268))
* **manager:** Add type hints for settings and schema properties ([9f43463](https://github.com/MilliPress/MilliBase/commit/9f43463099c1a2a6bd682a06bf91e1fd968be76c))

## [1.2.2](https://github.com/MilliPress/MilliBase/compare/v1.2.1...v1.2.2) (2026-03-15)


### Bug Fixes

* ensure build assets are always included in distributed package ([408a30b](https://github.com/MilliPress/MilliBase/commit/408a30b14b92a0244df0f829c930028963be0236))

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
