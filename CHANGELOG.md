# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]
### Added
...

### Changed
- The `Api::downloadAttachment` method now throws an exception, when attempting to download from a non-Jira website by [@aik099] (#240).
- The `$params` argument of the `Api::getWorklogs` method is now optional by [@aik099] (#244).
- The `$params` argument of the `Api::getTransitions` method is now optional by [@aik099] (#244).

### Removed
...

### Fixed
- The `CurlClient` in combination with cURL version < 7.33.0 was getting `426 Upgrade Required` error on any request to the Atlassian Cloud servers by [@aik099] (#239).
- The `PHPClient` on PHP < 8.0 was getting `426 Upgrade Required` error on any request to the Atlassian Cloud servers by [@aik099] (#239).
- The `Api::downloadAttachment` method wasn't working with `CurlClient` for Atlassian Cloud servers by [@aik099] (#240).

## [2.0.0-B1] - 2025-01-04
### Added
- Added `Api::getWorklogs` call for getting issue work logs by [@camspanos].
- Enhance `Api::getCreateMeta` call with ability (optional) to return issue fields by [@arnested].
- Added `Api::createRemotelink` call for creating linking issue with remote applications by [@elmi82].
- Added `Api::findVersionByName` call for getting project version information by it's name by [@jpastoor].
- Added `Api::updateVersion` call for editing version by [@jpastoor].
- Added `Api::releaseVersion` call for marking version as released by [@jpastoor].
- Added `Api::getAttachmentsMetaInformation` call for getting attachments meta information by [@N-M].
- Added `Api::getProjectComponents` call for getting project components by [@N-M].
- Added `Api::getProjectIssueTypes` call for getting project issue types and issue statuses connected to them by [@N-M].
- Added `Api::getResolutions` call for getting available issue resolutions by [@N-M].
- Allow configuring issues queried per page in `Walker` class by [@aik099].
- Added optional override for the filename in `Api::createAttachment` by [@betterphp]
- Allow getting issue count back from `Walker` class by [@aik099].
- Setup `.gitattributes` for better `CHANGELOG.md` merging by [@glensc].
- Added `Api::addWorklog` and `Api::deleteWorklog` calls for more control over the work logs [@dumconstantin] and [@aik099].
- Added `Api::getWorklogs` call for getting issue work logs by [@camspanos] (#37).
- Added `Api::createRemotelink` call for linking issue with its remote applications by [@elmi82] (#43).
- Added `Api::findVersionByName` call for getting project version information by its name by [@jpastoor] (#82).
- Added `Api::updateVersion` call for editing version by [@jpastoor] (#82).
- Added `Api::releaseVersion` call for marking version as released by [@jpastoor] (#82).
- Added `Api::getAttachmentsMetaInformation` call for getting attachments meta information by [@N-M] (#101).
- Added `Api::getProjectComponents` call for getting project components by [@N-M] (#104).
- Added `Api::getProjectIssueTypes` call for getting project issue types and issue statuses connected to them by [@N-M] (#104).
- Added `Api::getResolutions` call for getting available issue resolutions by [@N-M] (#104).
- Allow configuring issues queried per page in `Walker` class by [@aik099] (#142).
- Allow getting issue count back from `Walker` class by [@aik099] (#149).
- Setup `.gitattributes` for better `CHANGELOG.md` merging by [@glensc] (#185).

### Changed
- Classes/interfaces were renamed to use namespaces by [@chobie] (#21).
- Using PSR-4 autoloader from Composer by [@chobie].
- Minimal supported PHP version changed from 5.2 to 5.3 by [@chobie] (#21).
- The `Api::getPriorties` renamed into `Api::getPriorities` by [@josevh] and [@jpastoor] (#68).
- The `Api::setEndPoint` now also removes trailing slash from the given url by [@Procta] (#67).
- Added local cache to getResolutions by [@jpastoor] (#131).
- Renamed Api::api() parameter `$return_as_json` to `$return_as_array` by [@jpastoor] (#134).
- Renamed `Api::createRemotelink` to `Api::createRemoteLink` by [@glensc] (#183).
- The `CurlClient::sendRequest` is throwing exception, when `$data` parameter isn't an array and `$method` is GET by [@alopex06] (#100).
- Minimal supported PHP version changed from 5.3 to 5.6 by [@aik099] (#207).
- Enhance `Api::getCreateMeta` call with an optional ability (via the new `$expand` parameter) to return issue fields by [@arnested] (#26).
- Added an optional `$name` parameter (replaces `$options` parameter) to `Api::createAttachment` for specifying name of the uploaded file by [@betterphp] (#141).
- The `$method` parameter of the `Api::api` method is now mandatory (previously had `self::REQUEST_GET` value) by [@aik099] (#226).
- The `$data` parameter of the `ClientInterface::sendRequest` method is now mandatory (previously had `array()` value) by [@aik099] (#226).

### Fixed
- Attachments created using `PHPClient` were not accessible from JIRA by [@ubermuda] (#59).
- Inability to create attachment using `CurlClient` on PHP 5.6+ by [@shmaltorhbooks] (#52).
- The `Api::getIssueTypes` call wasn't working on JIRA 6.4+ due new `avatarId` parameter for issue types by [@addersuk] (#50).
- The `CurlClient` wasn't recognizing `201` response code as success (e.g. used by `/rest/api/2/issueLink` API call) by [@zuzmic] (#40).
- Anonymous access to JIRA from `CurlClient` wasn't working by [@digitalkaoz] (#32).
- Fixed PHP deprecation notice, when creating issue attachments via `CurlClient` on PHP 5.5+ by [@DerMika] (#86).
- The `Api::getRoles` call was always retuning an error by [@aik099] (#99).
- Attempt to make a `DELETE` API call using `CurlClient` wasn't working by [@aik099] (#115).
- Clearing local caches (statuses, priorities, fields and resolutions) on endpoint change by [@jpastoor] (#131).
- Error details from failed API calls were not available back from `Api::api method` call by [@betterphp] (#140).
- Warning about `count()` function usage on PHP 7.2, when searching for issues by [@aik099] (#174).
- The `Api::createRemotelink` wasn't updating an existing remote link, because given `$global_id` parameter was incorrectly passed to the Jira by  [@glensc] (#178).
- The `Api::getIssueTypes` was always returning an error, because `entityId`, `hierarchyLevel` and `untranslatedName` issue type properties weren't supported by [@aik099] (#208).
- The `PHPClient` was sending wrong `Content-Type` header for GET requests by [@aik099] (#108).
- Attempt to make a `DELETE` API call using `PHPClient` wasn't working by [@aik099] (#108).
- The `PHPClient` thrown exceptions weren't inline with `CurlClient` thrown exceptions by [@aik099] (#108).
- Fixed the `CurlClient` inability to perform an SSL connection from macOS due to locked HTTP protocol version by [@benPesso] (#147).
- The `Api::getIssueTypes` method was always throwing an error due to `scope` issue type parameter wasn't supported by [@danillofb] (#181).

## [1.0.0] - 2014-07-27
### Added
- Initial release.

[Unreleased]: https://github.com/chobie/jira-api-restclient/compare/v2.0.0-B1...HEAD
[2.0.0-B1]: https://github.com/chobie/jira-api-restclient/compare/v1.0.0...v2.0.0-B1
[1.0.0]: https://github.com/chobie/jira-api-restclient/compare/b86f47129509bb27ae11d136fed67b70a27fd3be...v1.0.0
[@camspanos]: https://github.com/camspanos
[@arnested]: https://github.com/arnested
[@elmi82]: https://github.com/elmi82
[@jpastoor]: https://github.com/jpastoor
[@N-M]: https://github.com/N-M
[@chobie]: https://github.com/chobie
[@josevh]: https://github.com/josevh
[@Procta]: https://github.com/Procta
[@ubermuda]: https://github.com/ubermuda
[@shmaltorhbooks]: https://github.com/shmaltorhbooks
[@addersuk]: https://github.com/addersuk
[@zuzmic]: https://github.com/zuzmic
[@digitalkaoz]: https://github.com/digitalkaoz
[@DerMika]: https://github.com/DerMika
[@aik099]: https://github.com/aik099
[@betterphp]: https://github.com/betterphp
[@glensc]: https://github.com/glensc
[@dumconstantin]: https://github.com/dumconstantin
[@alopex06]: https://github.com/alopex06
[@benPesso]: https://github.com/benPesso
[@danillofb]: https://github.com/danillofb
