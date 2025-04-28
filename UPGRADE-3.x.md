
## 3.0.0

The upgrade from Sulu 2.6 to Sulu 3.0 is a major upgrade and will require some migration steps.

### Pre Update step to 3.0 PHPCR Migration

Before upgrading to 3.0 make sure you have installed the latest Sulu 2.x versions of Sulu and the Sulu bundles.
Make sure that you have all PHPCR migration run via:

```shell
php bin/adminconsole phpcr:migrations:migrate
```

### Pre Update step to 3.0 Sulu Article Bundle

Due to the fact that in Sulu 3.0 the SuluArticleBundle is merged into the core Sulu package, if you have the
sulu/article-bundle installed, you need to update the sulu/article-bundle dependency to the latest version before
updating the sulu/sulu dependency. If the sulu/article-bundle is not installed, this step is not necessary.

Ensure that the [Version202407111600](https://github.com/sulu/SuluArticleBundle/blob/2.6/Resources/phpcr-migrations/Version202407111600.php) migration is executed. This migration is required to migrate the old article
structure to the new one. After that you can remove the old article bundle from your code.

```shell
composer remove sulu/article-bundle
# composer remove elasticsearch/elasticsearch # can also be removed if not further required
```

Disable the old SuluArticleBundle `config/bundles.php`:

```diff
// config/bundles.php

return [
     // ...
-    Sulu\Bundle\ArticleBundle\SuluArticleBundle::class => ['all' => true],
-    ONGR\ElasticsearchBundle\ONGRElasticsearchBundle::class => ['all' => true],
```

### Upgrade to Sulu 3.0 and register new bundles

Now upgrade the dependencies to Sulu 3.0:

```shell
composer require sulu/sulu:"3.0.*"
```

After that you need to register the new Sulu bundles in your `config/bundles.php`:

```diff
// config/bundles.php

return [
     // ...
-    Sulu\Bundle\SnippetBundle\SuluSnippetBundle::class => ['all' => true],
-    Sulu\Bundle\ArticleBundle\SuluArticleBundle::class => ['all' => true],
-    ONGR\ElasticsearchBundle\ONGRElasticsearchBundle::class => ['all' => true],
-    Sulu\Bundle\CustomUrlBundle\SuluCustomUrlBundle::class => ['all' => true],

+    Sulu\Content\Infrastructure\Symfony\HttpKernel\SuluContentBundle::class => ['all' => true],
+    Sulu\Route\Infrastructure\Symfony\HttpKernel\SuluRouteBundle::class => ['all' => true],
+    Sulu\Messenger\Infrastructure\Symfony\HttpKernel\SuluMessengerBundle::class => ['all' => true],
+    Sulu\Article\Infrastructure\Symfony\HttpKernel\SuluArticleBundle::class => ['all' => true],
+    Sulu\Snippet\Infrastructure\Symfony\HttpKernel\SuluSnippetBundle::class => ['all' => true],
+    Sulu\Page\Infrastructure\Symfony\HttpKernel\SuluPageBundle::class => ['all' => true],
```

### Register new bundle routes

Then you need to update the route configuration in your `config/routes/sulu_admin.yaml`:

```diff
-sulu_core:
-    type: rest
-    resource: "@SuluCoreBundle/Resources/config/routing_api.yml"
-    prefix: /admin/api

- sulu_custom_urls_api:
-     type: rest
-     resource: "@SuluCustomUrlBundle/Resources/config/routing_api.yml"
-     prefix: /admin/api

 sulu_snippet_api:
-    resource: "@SuluSnippetBundle/Resources/config/routing_api.yml"
-    type: rest
+    resource: "@SuluSnippetBundle/config/routing_admin_api.yaml"
     prefix: /admin/api

+sulu_next_page_api:
+    resource: "@SuluNextPageBundle/config/routing_admin_api.yaml"
+    prefix: /admin/api
+
+sulu_article_api:
+    resource: "@SuluArticleBundle/config/routing_admin_api.yaml"
+    prefix: /admin/api
```

### Add new Content storage tables

The new content storage architecture requires a new database schema. You can execute the following sql statements
to update your database schema.

#### RouteBundle

```sql
CREATE TABLE ro_next_routes (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, site VARCHAR(32) DEFAULT NULL, locale VARCHAR(15) NOT NULL, slug VARCHAR(144) NOT NULL, resource_key VARCHAR(32) NOT NULL, resource_id VARCHAR(70) NOT NULL, INDEX IDX_BD51B2DA727ACA70 (parent_id), INDEX ro_routes_resource_idx (locale, resource_key, resource_id), UNIQUE INDEX ro_routes_unique (site, locale, slug), PRIMARY KEY(id));
ALTER TABLE ro_next_routes ADD CONSTRAINT FK_BD51B2DA727ACA70 FOREIGN KEY (parent_id) REFERENCES ro_next_routes (id) ON DELETE SET NULL;
```

#### PageBundle

```sql
CREATE TABLE pa_page_dimension_contents (id INT AUTO_INCREMENT NOT NULL, route_id INT DEFAULT NULL, author_id INT DEFAULT NULL, title VARCHAR(191) DEFAULT NULL, stage VARCHAR(15) NOT NULL, locale VARCHAR(15) DEFAULT NULL, ghostLocale VARCHAR(15) DEFAULT NULL, availableLocales JSON DEFAULT NULL, shadowLocale VARCHAR(15) DEFAULT NULL, shadowLocales JSON DEFAULT NULL, templateKey VARCHAR(31) DEFAULT NULL, templateData JSON NOT NULL, seoTitle VARCHAR(255) DEFAULT NULL, seoDescription LONGTEXT DEFAULT NULL, seoKeywords LONGTEXT DEFAULT NULL, seoCanonicalUrl LONGTEXT DEFAULT NULL, seoNoIndex TINYINT(1) NOT NULL, seoNoFollow TINYINT(1) NOT NULL, seoHideInSitemap TINYINT(1) NOT NULL, excerptTitle VARCHAR(255) DEFAULT NULL, excerptMore VARCHAR(63) DEFAULT NULL, excerptDescription LONGTEXT DEFAULT NULL, excerptImageId INT DEFAULT NULL, excerptIconId INT DEFAULT NULL, authored DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', lastModified DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', workflowPlace VARCHAR(31) DEFAULT NULL, workflowPublished DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', pageUuid VARCHAR(255) NOT NULL, INDEX IDX_209A42C034ECB4E6 (route_id), INDEX IDX_209A42C0F099EEF3 (pageUuid), INDEX IDX_209A42C0F675F31B (author_id), INDEX idx_pa_page_dimension_contents_dimension (stage, locale), INDEX idx_pa_page_dimension_contents_locale (locale), INDEX idx_pa_page_dimension_contents_stage (stage), INDEX idx_pa_page_dimension_contents_template_key (templateKey), INDEX idx_pa_page_dimension_contents_workflow_place (workflowPlace), INDEX idx_pa_page_dimension_contents_workflow_published (workflowPublished), INDEX IDX_209A42C02F5A5F5D (excerptImageId), INDEX IDX_209A42C016996F78 (excerptIconId), PRIMARY KEY(id));
CREATE TABLE pa_page_dimension_content_excerpt_tags (page_dimension_content_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_66C81FDB67C2CFD5 (page_dimension_content_id), INDEX IDX_66C81FDBBAD26311 (tag_id), PRIMARY KEY(page_dimension_content_id, tag_id));
CREATE TABLE pa_page_dimension_content_excerpt_categories (page_dimension_content_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_BE45C16867C2CFD5 (page_dimension_content_id), INDEX IDX_BE45C16812469DE2 (category_id), PRIMARY KEY(page_dimension_content_id, category_id));
CREATE TABLE pa_page_dimension_content_navigation_contexts (id INT AUTO_INCREMENT NOT NULL, page_dimension_content_id INT NOT NULL, name VARCHAR(64) NOT NULL, INDEX IDX_4C5FD8F767C2CFD5 (page_dimension_content_id), INDEX idx_page_navigation_context (name), PRIMARY KEY(id));
CREATE TABLE pa_pages (uuid VARCHAR(255) NOT NULL, parent_id VARCHAR(255) DEFAULT NULL, webspaceKey VARCHAR(64) NOT NULL, lft INT NOT NULL, rgt INT NOT NULL, depth INT NOT NULL, created DATETIME NOT NULL, changed DATETIME NOT NULL, idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_FF3DA1E2727ACA70 (parent_id), INDEX IDX_FF3DA1E2DBF11E1D (idUsersCreator), INDEX IDX_FF3DA1E230D07CD5 (idUsersChanger), PRIMARY KEY(uuid));
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C034ECB4E6 FOREIGN KEY (route_id) REFERENCES ro_next_routes (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C0F099EEF3 FOREIGN KEY (pageUuid) REFERENCES pa_pages (uuid) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C0F675F31B FOREIGN KEY (author_id) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C02F5A5F5D FOREIGN KEY (excerptImageId) REFERENCES me_media (id) ON DELETE SET NULL;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C016996F78 FOREIGN KEY (excerptIconId) REFERENCES me_media (id) ON DELETE SET NULL;
ALTER TABLE pa_page_dimension_content_excerpt_tags ADD CONSTRAINT FK_66C81FDB67C2CFD5 FOREIGN KEY (page_dimension_content_id) REFERENCES pa_page_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_content_excerpt_tags ADD CONSTRAINT FK_66C81FDBBAD26311 FOREIGN KEY (tag_id) REFERENCES ta_tags (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_content_excerpt_categories ADD CONSTRAINT FK_BE45C16867C2CFD5 FOREIGN KEY (page_dimension_content_id) REFERENCES pa_page_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_content_excerpt_categories ADD CONSTRAINT FK_BE45C16812469DE2 FOREIGN KEY (category_id) REFERENCES ca_categories (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_content_navigation_contexts ADD CONSTRAINT FK_4C5FD8F767C2CFD5 FOREIGN KEY (page_dimension_content_id) REFERENCES pa_page_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE pa_pages ADD CONSTRAINT FK_FF3DA1E2727ACA70 FOREIGN KEY (parent_id) REFERENCES pa_pages (uuid) ON DELETE CASCADE;
ALTER TABLE pa_pages ADD CONSTRAINT FK_FF3DA1E2DBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE pa_pages ADD CONSTRAINT FK_FF3DA1E230D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
```

#### SnippetBundle

```sql
CREATE TABLE sn_snippet_dimension_contents (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(191) DEFAULT NULL, stage VARCHAR(15) NOT NULL, locale VARCHAR(15) DEFAULT NULL, ghostLocale VARCHAR(15) DEFAULT NULL, availableLocales JSON DEFAULT NULL, templateKey VARCHAR(31) DEFAULT NULL, templateData JSON NOT NULL, excerptTitle VARCHAR(255) DEFAULT NULL, excerptMore VARCHAR(63) DEFAULT NULL, excerptDescription LONGTEXT DEFAULT NULL, excerptImageId INT DEFAULT NULL, excerptIconId INT DEFAULT NULL, workflowPlace VARCHAR(31) DEFAULT NULL, workflowPublished DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', snippetUuid VARCHAR(255) NOT NULL, INDEX IDX_46D6814477F33FFB (snippetUuid), INDEX idx_sn_snippet_dimension_contents_dimension (stage, locale), INDEX idx_sn_snippet_dimension_contents_locale (locale), INDEX idx_sn_snippet_dimension_contents_stage (stage), INDEX idx_sn_snippet_dimension_contents_template_key (templateKey), INDEX idx_sn_snippet_dimension_contents_workflow_place (workflowPlace), INDEX idx_sn_snippet_dimension_contents_workflow_published (workflowPublished), INDEX IDX_46D681442F5A5F5D (excerptImageId), INDEX IDX_46D6814416996F78 (excerptIconId), PRIMARY KEY(id));
CREATE TABLE sn_snippet_dimension_content_excerpt_tags (snippet_dimension_content_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_96BD1E357891499D (snippet_dimension_content_id), INDEX IDX_96BD1E35BAD26311 (tag_id), PRIMARY KEY(snippet_dimension_content_id, tag_id));
CREATE TABLE sn_snippet_dimension_content_excerpt_categories (snippet_dimension_content_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_464EB1547891499D (snippet_dimension_content_id), INDEX IDX_464EB15412469DE2 (category_id), PRIMARY KEY(snippet_dimension_content_id, category_id));
CREATE TABLE sn_snippets (uuid VARCHAR(255) NOT NULL, created DATETIME NOT NULL, changed DATETIME NOT NULL, idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_E68115CFDBF11E1D (idUsersCreator), INDEX IDX_E68115CF30D07CD5 (idUsersChanger), PRIMARY KEY(uuid));
ALTER TABLE sn_snippet_dimension_contents ADD CONSTRAINT FK_46D6814477F33FFB FOREIGN KEY (snippetUuid) REFERENCES sn_snippets (uuid) ON DELETE CASCADE;
ALTER TABLE sn_snippet_dimension_contents ADD CONSTRAINT FK_46D681442F5A5F5D FOREIGN KEY (excerptImageId) REFERENCES me_media (id) ON DELETE SET NULL;
ALTER TABLE sn_snippet_dimension_contents ADD CONSTRAINT FK_46D6814416996F78 FOREIGN KEY (excerptIconId) REFERENCES me_media (id) ON DELETE SET NULL;
ALTER TABLE sn_snippet_dimension_content_excerpt_tags ADD CONSTRAINT FK_96BD1E357891499D FOREIGN KEY (snippet_dimension_content_id) REFERENCES sn_snippet_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE sn_snippet_dimension_content_excerpt_tags ADD CONSTRAINT FK_96BD1E35BAD26311 FOREIGN KEY (tag_id) REFERENCES ta_tags (id) ON DELETE CASCADE;
ALTER TABLE sn_snippet_dimension_content_excerpt_categories ADD CONSTRAINT FK_464EB1547891499D FOREIGN KEY (snippet_dimension_content_id) REFERENCES sn_snippet_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE sn_snippet_dimension_content_excerpt_categories ADD CONSTRAINT FK_464EB15412469DE2 FOREIGN KEY (category_id) REFERENCES ca_categories (id) ON DELETE CASCADE;
ALTER TABLE sn_snippets ADD CONSTRAINT FK_E68115CFDBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE sn_snippets ADD CONSTRAINT FK_E68115CF30D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
```

#### ArticleBundle

```sql
CREATE TABLE ar_article_dimension_contents (id INT AUTO_INCREMENT NOT NULL, route_id INT DEFAULT NULL, author_id INT DEFAULT NULL, title VARCHAR(191) DEFAULT NULL, stage VARCHAR(15) NOT NULL, locale VARCHAR(15) DEFAULT NULL, ghostLocale VARCHAR(15) DEFAULT NULL, availableLocales JSON DEFAULT NULL, shadowLocale VARCHAR(15) DEFAULT NULL, shadowLocales JSON DEFAULT NULL, templateKey VARCHAR(31) DEFAULT NULL, templateData JSON NOT NULL, seoTitle VARCHAR(255) DEFAULT NULL, seoDescription LONGTEXT DEFAULT NULL, seoKeywords LONGTEXT DEFAULT NULL, seoCanonicalUrl LONGTEXT DEFAULT NULL, seoNoIndex TINYINT(1) NOT NULL, seoNoFollow TINYINT(1) NOT NULL, seoHideInSitemap TINYINT(1) NOT NULL, excerptTitle VARCHAR(255) DEFAULT NULL, excerptMore VARCHAR(63) DEFAULT NULL, excerptDescription LONGTEXT DEFAULT NULL, excerptImageId INT DEFAULT NULL, excerptIconId INT DEFAULT NULL, mainWebspace VARCHAR(255) DEFAULT NULL, authored DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', lastModified DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', workflowPlace VARCHAR(31) DEFAULT NULL, workflowPublished DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', articleUuid VARCHAR(255) NOT NULL, INDEX IDX_5674F7BF34ECB4E6 (route_id), INDEX IDX_5674F7BFAE39C518 (articleUuid), INDEX IDX_5674F7BFF675F31B (author_id), INDEX idx_ar_article_dimension_contents_dimension (stage, locale), INDEX idx_ar_article_dimension_contents_locale (locale), INDEX idx_ar_article_dimension_contents_stage (stage), INDEX idx_ar_article_dimension_contents_template_key (templateKey), INDEX idx_ar_article_dimension_contents_workflow_place (workflowPlace), INDEX idx_ar_article_dimension_contents_workflow_published (workflowPublished), INDEX IDX_5674F7BF2F5A5F5D (excerptImageId), INDEX IDX_5674F7BF16996F78 (excerptIconId), PRIMARY KEY(id));
CREATE TABLE ar_article_dimension_content_excerpt_tags (article_dimension_content_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_B45854027C1747D1 (article_dimension_content_id), INDEX IDX_B4585402BAD26311 (tag_id), PRIMARY KEY(article_dimension_content_id, tag_id));
CREATE TABLE ar_article_dimension_content_excerpt_categories (article_dimension_content_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_971AE52D7C1747D1 (article_dimension_content_id), INDEX IDX_971AE52D12469DE2 (category_id), PRIMARY KEY(article_dimension_content_id, category_id));
CREATE TABLE ar_articles (uuid VARCHAR(255) NOT NULL, created DATETIME NOT NULL, changed DATETIME NOT NULL, idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_7F75CD17DBF11E1D (idUsersCreator), INDEX IDX_7F75CD1730D07CD5 (idUsersChanger), PRIMARY KEY(uuid));
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BF34ECB4E6 FOREIGN KEY (route_id) REFERENCES ro_next_routes (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BFAE39C518 FOREIGN KEY (articleUuid) REFERENCES ar_articles (uuid) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BFF675F31B FOREIGN KEY (author_id) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BF2F5A5F5D FOREIGN KEY (excerptImageId) REFERENCES me_media (id) ON DELETE SET NULL;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BF16996F78 FOREIGN KEY (excerptIconId) REFERENCES me_media (id) ON DELETE SET NULL;
ALTER TABLE ar_article_dimension_content_excerpt_tags ADD CONSTRAINT FK_B45854027C1747D1 FOREIGN KEY (article_dimension_content_id) REFERENCES ar_article_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_content_excerpt_tags ADD CONSTRAINT FK_B4585402BAD26311 FOREIGN KEY (tag_id) REFERENCES ta_tags (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_content_excerpt_categories ADD CONSTRAINT FK_971AE52D7C1747D1 FOREIGN KEY (article_dimension_content_id) REFERENCES ar_article_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_content_excerpt_categories ADD CONSTRAINT FK_971AE52D12469DE2 FOREIGN KEY (category_id) REFERENCES ca_categories (id) ON DELETE CASCADE;
ALTER TABLE ar_articles ADD CONSTRAINT FK_7F75CD17DBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE ar_articles ADD CONSTRAINT FK_7F75CD1730D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
```

### Migrate Permission settings

```sql
UPDATE `se_permissions` SET `context` = 'sulu.article.articles' WHERE `context` = 'sulu.modules.articles';
UPDATE `se_permissions` SET `context` = 'sulu.snippet.snippets' WHERE `context` = 'sulu.global.snippets';
```

### Remove legacy user settings

This step is optional but highly recommended, removing legacy user settings from the database helps ensure compatibility
with the new content storage architecture. Some columns from the old settings may no longer exist in the updated schema.
Retaining outdated data can lead to issues or exceptions in the admin interface, especially if user settings reference
fields that are no longer available.

To safely remove these obsolete settings, execute the following SQL commands:

```sql
DELETE FROM se_user_settings WHERE settingsKey LIKE 'sulu_admin.list_store.articles%';
DELETE FROM se_user_settings WHERE settingsKey LIKE 'sulu_admin.list_store.snippets%';
DELETE FROM se_user_settings WHERE settingsKey LIKE 'sulu_admin.list_store.pages%';
```

### Upgrading Data from Sulu 2.6 to Sulu 3.0

To migrate the data from PHPCR to the new content storage a migration bundle was developed. 

```shell
composer require sulu/phpcr-migration-bundle
```

Add the new migration bundle to your `config/bundles.php`:

```diff
// config/bundles.php

return [
    // ...
+   Sulu\Bundle\PhpcrMigrationBundle\SuluPhpcrMigrationBundle::class => ['all' => true],
```

Configure the SuluPhpcrMigrationBundle in `config/packages/sulu_phpcr_migration.yaml`:

> If you are currently using Jackrabbit, use the "jackrabbit://" based DSN string.
> After the upgrade, Apache Jackrabbit is no longer used by Sulu’s new content storage and can be removed from
> your projects in most situations.

```yaml
# config/packages/sulu_phpcr_migration.yaml

sulu_phpcr_migration:
    # dbal://<dbalConnection>?workspace=<workspaceName>
    # jackrabbit://<user>:<password>@<host>:<port>/server?workspace=<workspaceName>
    #    DSN: "dbal://default?workspace=%env(PHPCR_WORKSPACE)%"
    #    DSN: "jackrabbit://admin:admin@127.0.0.1:8080/server?workspace=%env(PHPCR_WORKSPACE)%"
    DSN: "dbal://default?workspace=%env(PHPCR_WORKSPACE)%"
    target:
        dbal:
            connection: default
```

Please ensure that you have executed all the database migrations for version 3.0. Once finished, you 
can run the following command to update the content structure:

```shell
php bin/adminconsole sulu:phpcr-migration:migrate
```

In case of some errors on customized code, you can try to fix it and rerun the command. The migration command can be
rerun, the existing already migrated content will be overwritten and not duplicated.
If everything is done and the migration is successful, you can log in to the Sulu admin interface, set the permissions
for the articles and snippets and check if everything is working as expected.

### Upgrade resourceLocator and route property type

The new content structure used in Sulu 3.0 requires that all the `resource_locators` or `route` properties must be
renamed to `url` in your templates and use the `route` always.

```diff
-        <property name="routePath" type="route">
+        <property name="url" type="route">
            <meta>
                <title lang="en">Resourcelocator</title>
                <title lang="de">Adresse</title>
            </meta>

            <tag name="sulu_article.article_route"/>
        </property>
```

### Upgrade the Controller references

The controller in the page/article templates have to be adjusted use the new controller
from the SuluContentBundle. Be aware that also your custom controllers have to be modified to extend from the new one.

```diff
-        <controller class="Sulu\Bundle\ArticleBundle\Controller\ArticleController"/>
-        <controller>Sulu\Bundle\WebsiteBundle\Controller\DefaultController::indexAction</controller>
+        <controller>Sulu\Content\UserInterface\Controller\Website\ContentController::indexAction</controller>
```

### Page, CustomUrl, Snippet JS includes path changed

The paths to the page, custom url and snippet bundle has changed so its JS package
paths need also be changed. This step is normally automatically done by `bin/console sulu:admin:update-build` command,
kept here for completeness:

```diff
-        "sulu-custom-url-bundle": "file:../../vendor/sulu/sulu/src/Sulu/Bundle/CustomUrlBundle/Resources/js",
+        "sulu-custom-url-bundle": "file:../../vendor/sulu/sulu/packages/custom-url/assets/js",
-        "sulu-page-bundle": "file:../../vendor/sulu/sulu/src/Sulu/Bundle/PageBundle/Resources/js",
+        "sulu-page-bundle": "file:../../vendor/sulu/sulu/packages/page/assets/js",
-        "sulu-snippet-bundle": "file:../../vendor/sulu/sulu/src/Sulu/Bundle/SnippetBundle/Resources/js",
+        "sulu-snippet-bundle": "file:../../vendor/sulu/sulu/packages/snippet/assets/js",
```

### Removing deprecated twig functions

- `sulu_meta_alternate` (use the SEO template instead `@SuluWebsite/Extension/seo.html.twig`)
- `sulu_meta_seo` (use the SEO template instead `@SuluWebsite/Extension/seo.html.twig`)
- `sulu_seo` (use the SEO template instead `@SuluWebsite/Extension/seo.html.twig`)

This also includes the container services:
- `sulu_website.twig.meta`
- `sulu_website.twig.seo`
and the parameters
- `%sulu_website.twig.meta.class%`
- `%sulu_website.twig.seo.class%`

### Remove deprecated sulu_website twig attributes configuration

The configuration for the Twig attribute in `config/packages/sulu_website.yaml` needs to be removed.

```diff
sulu_website:
-    twig:
-        attributes:
-            urls: false
-            path: false
```

If you have nothing else configured, the whole file can be removed.

### Removing "modules" from Permissions

The unused column on the permissions table has been removed. This also requires some migration on the table, to recreate
an index:

```sql
DROP INDEX UNIQ_5CEC3EEAE25D857EC242628A1FA6DDA ON se_permissions;
ALTER TABLE se_permissions DROP module;
CREATE UNIQUE INDEX UNIQ_5CEC3EEAE25D857EA1FA6DDA ON se_permissions (context, idRoles);
```

### Removed `SecurityType`

Removed the `Sulu\Bundle\SecurityBundle\Entity\SecurityType` class and its fixtures. This also includes database migrations:

```sql
ALTER TABLE se_roles DROP FOREIGN KEY FK_13B749A0D02106C0;
DROP TABLE se_security_types;
DROP INDEX IDX_13B749A0D02106C0 ON se_roles;
ALTER TABLE se_roles DROP idSecurityTypes;
```

And the container parameters has been removed:

- `sulu_security.security_types.fixture`

### Groups and User Groups have been removed

This includes the following services:
- `sulu_security.group_repository`
- `sulu_security.group_controller`

These unused parameters have been removed:
- `sulu_security.group_repository.class`
- `sulu_security.entity_group.class`
- `sulu_security.entity.group`

The resource routes has been removed:

```diff
- sulu_security.groups:
-    type: rest
-    name_prefix: sulu_security.
-    resource: sulu_security.group_controller
```

The `se_user_groups` and `se_groups` table were removed from the database:

```sql
ALTER TABLE se_group_roles DROP FOREIGN KEY FK_9713F725937C91EA;
ALTER TABLE se_group_roles DROP FOREIGN KEY FK_9713F725A1FA6DDA;
ALTER TABLE se_groups DROP FOREIGN KEY FK_231E44EC30D07CD5;
ALTER TABLE se_groups DROP FOREIGN KEY FK_231E44ECBF274AB0;
ALTER TABLE se_groups DROP FOREIGN KEY FK_231E44ECDBF11E1D;
ALTER TABLE se_user_groups DROP FOREIGN KEY FK_E43ED0C8347E6F4;
ALTER TABLE se_user_groups DROP FOREIGN KEY FK_E43ED0C8937C91EA;
DROP TABLE se_group_roles;
DROP TABLE se_groups;
DROP TABLE se_user_groups;
```

### Preview Services changed

Most of the PreviewBundle services are now internal and only the `PreviewDefaultsProviderInterface` is
now the way to communicate with the Bundle.

### Changed Media Format HTTP Response Headers

Removed `Pragma` & `Expires` HTTP headers, as the `Cache-Control` header is enough.

If you still want to readd them use `sulu_media.format_manager.response_headers` configuration.

### Removed deprecations for 3.0

Removed classes / services:

- `Sulu/Bundle/MarkupBundle/Listener/SwiftMailerListener`
- `Sulu\Bundle\DocumentManagerBundle\Slugifier\Urlizer`
- `Sulu\\Bundle\\CategoryBundle\\DependencyInjection\\DeprecationCompilerPass`
- `Sulu\\Bundle\\SecurityBundle\\DataFixtures\\ORM\\LoadSecurityTypes`
- `Sulu\\Bundle\\SecurityBundle\\Controller\\ContextsController`
- `Sulu\Component\Rest\Listing\ListQueryBuilder`
- `Sulu\Component\Rest\Listing\ListRepository`
- `Sulu\Component\Rest\Listing\ListRestHelper`
- `Sulu\\Bundle\\CoreBundle\\Controller\\LocalizationController`
- `Sulu\\Bundle\\MediaBundle\\Media\\Storage\\AzureBlobStorage`
- `Sulu\\Bundle\\MediaBundle\\Media\\Storage\\GoogleCloudStorage`
- `Sulu\\Bundle\\MediaBundle\\Media\\Storage\\LocalStorage`
- `Sulu\\Bundle\\MediaBundle\\Media\\Storage\\S3Storage`
- `Sulu\\Bundle\\MediaBundle\\DependencyInjection\\S3ClientCompilerPass` (internal)
- `Sulu\\Bundle\\AdminBundle\\Command\\DownloadBuildCommand`
- `Sulu\\Component\\Rest\\ListBuilder\\ListRepresentation`

Removed deprecated functions and properties:

- `Sulu\Component\Security\Event\PermissionUpdateEvent::getSecurityIdentity`
- `Sulu\Component\Webspace\Portal::getXDefaultLocalization`
- `Sulu\Component\Webspace\Portal::setXDefaultLocalization`
- `Sulu\Component\Localization\Localization::isXDefault`
- `Sulu\Bundle\ContactBundle\Controller\AccountController::$contactEntityKey`
- `Sulu\Bundle\ContactBundle\Controller\AccountController::$entityKey`
- `Sulu\Bundle\WebsiteBundle\Controller\AnalyticsController::$entityKey`
- `Sulu\Bundle\CategoryBundle\Controller\CategoryController::$entityKey`
- `Sulu\Bundle\MediaBundle\Controller\CollectionController::$entityKey`
- `Sulu\Bundle\MediaBundle\Controller\MediaController::$entityKey`
- `Sulu\Bundle\PageBundle\Controller\PageController::$entityKey`
- `Sulu\Bundle\TagBundle\Controller\TagController::$entityKey`
- `Sulu\Bundle\SecurityBundle\Controller\UserController::$entityKey`
- `Sulu\Bundle\ContactBundle\Controller\ContactTitleController::$entityKey`
- `Sulu\Bundle\ContactBundle\Controller\ContactController::$entityKey`
- `Sulu\Bundle\ContactBundle\Controller\PositionController::$entityKey`
- `Sulu\Component\Cache\Memoize::memoize()`
- `Sulu\Component\Cache\MemoizeInterface::memoize()`

Removed unused arguments:

- `Sulu\Component\Webspace\Analyzer\Attributes\WebsiteRequestProcessor::__construct` `$contentMapper` (2nd argument) removed
- `Sulu\\Bundle\\SecurityBundle\\UserManager\\UserManager::__construct` `$groupRepository` (4th argument) removed
- `Sulu\\Bundle\\SecurityBundle\\Admin\\SecurityAdmin::__construct` `$urlGenerator` (3rd argument) removed

### Moved classes for 3.0:

- `Sulu\Bundle\CoreBundle\ExpressionLanguage\ContainerExpressionLanguageProvider`: `Sulu\Bundle\AdminBundle\ExpressionLanguage\ContainerExpressionLanguageProvider`

### Moved services for 3.0:

- `sulu_core.expression_language`: `sulu_admin.expression_language`
- `sulu_core.symfony_expression_language_provider`: `sulu_admin.symfony_expression_language_provider`

### Piwik replaced with Matomo script

The script provided by Sulu for the piwik implementation has been updated to use mataomo path so the script is now pointing to matomo.php instead of the piwik.php file.

### Removing deprecated guzzle integration

As part of the update of flysystem the support for the guzzle client package `guzzlehttp/guzzle` has been removed. If you need it you need to manually require it.

The `GoogleGeolocator` and the `NominatimGeolocator` no longer support the Guzzle client and require a `Symfony\HttpClient` client instead.

### Updating to flysystem 3

The Sulu media storage uses now the Flysystem Bundle which need to be configured:

```yaml
# config/packages/flysystem.yaml
flysystem:
    storages:
        default.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/uploads/media'
```

Here are some examples on how to migrate individual providers:

<details>
  <summary>Sulu Media S3 to Flysystem S3</summary>

Before:

```yaml
sulu_media:
    storage: s3
    storages:
        s3:
            key: 'your aws s3 key'
            secret: 'your aws s3 secret'
            bucket_name: 'your aws s3 bucket name'
            path_prefix: 'optional/path/prefix'
            public_url: 'http://some_cdn.com'
            region: 'eu-west-1'
```

New:

```yaml
flysystem:
    storages:
        default.storage:
            adapter: 'aws'
            public_url: 'http://some_cdn.com'
            options:
                client: 'aws_client_service' # see service below
                key: ''
                secret: ''
                bucket: 'bucket_name'
                prefix: 'optional/path/prefix'
                streamReads: true
services:
    aws_client_service:
        class: Aws\\S3\\S3Client
        arguments:
            - [] # Put the value of the parameter "sulu_media.media.storage.s3.arguments" here (empty array by default)
```

</details>

<details>
  <summary>Sulu Google Cloud Config to Flysystem S3</summary>

> [!NOTE]  
> If you were using `superbalist/flysystem-google-storage` replace it with the official package version `league/flysystem-google-cloud-storage`.

Before:

```yaml
sulu_media:
    storage: google_cloud
    storages:
        google_cloud:
            key_file_path: '/path/to/key.json'
            bucket_name: 'sulu-bucket'
            path_prefix: 'optional path prefix'
```


New:
```yaml
flysystem:
    storages:
        default.storage:
            adapter: 'gcloud'
            options:
                client: 'gcloud_client_service' # The service ID of the Google\\Cloud\\Storage\\StorageClient instance
                bucket: 'bucket_name'
                prefix: 'optional/path/prefix'
```

</details>

<details>
  <summary>Sulu Azure Config to Flysystem Config</summary>

Before:

```yaml
sulu_media:
    storage: google_cloud
    storages:
        google_cloud:
            key_file_path: '/path/to/key.json'
            bucket_name: 'sulu-bucket'
            path_prefix: 'optional path prefix'
```

New

```yaml
flysystem:
    storages:
        default.storage:
            adapter: 'azure'
            options:
                client: 'azure_client_service' # The service ID of the MicrosoftAzure\Storage\Blob\BlobRestProxy instance
                container: 'container_name'
                prefix: 'optional/path/prefix'
```

</details>

If you want use a [different storage](https://github.com/thephpleague/flysystem-bundle/blob/3.x/docs/2-cloud-storage-providers.md) for Sulu you can configure it here:

```yaml
# config/packages/sulu_media.yaml
sulu_media:
    storage:
        flysystem_service: 'default.storage' # this is default and not required to be configured.
```

This will only create the service `sulu_media.storage` as the alias to `sulu_media.storage.*` services has been removed.
