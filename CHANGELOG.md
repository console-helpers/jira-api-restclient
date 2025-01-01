# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]
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

### Changed
- Classes/interfaces were renamed to use namespaces by [@chobie].
- Using PSR-4 autoloader from Composer by [@chobie].
- Minimal supported PHP version changed from 5.2 to 5.3 by [@chobie].
- The `Api::getPriorties` renamed into `Api::getPriorities` by [@josevh].
- Remove trailing slash from endpoint url by [@Procta].
- Added local cache to getResolutions by [@jpastoor].
- Renamed Api::api() parameter $return_as_json to $return_as_array by [@jpastoor].
- Renamed `Api::createRemotelink` to `Api::createRemoteLink` by [@glensc].
- Minimal supported PHP version changed from 5.3 to 5.6 by [@aik099].

### Removed
...

### Fixed
- Attachments created using `PHPClient` were not accessible from JIRA by [@ubermuda].
- Inability to create attachment using `CurlClient` on PHP 5.6+ by [@shmaltorhbooks].
- The `Api::getIssueTypes` call wasn't working on JIRA 6.4+ due new `avatarId` parameter for issue types by [@addersuk].
- The `CurlClient` wasn't recognizing `201` response code as success (e.g. used by `/rest/api/2/issueLink` API call) by [@zuzmic].
- Anonymous access to JIRA from `CurlClient` wasn't working by [@digitalkaoz].
- Fixed PHP deprecation notice, when creating issue attachments via `CurlClient` on PHP 5.5+ by [@DerMika].
- The `Api::getRoles` call was always retuning an error by [@aik099].
- Attempt to make a `DELETE` API call using `CurlClient` wasn't working by [@aik099].
- Clearing local caches (statuses, priorities, fields and resolutions) on endpoint change by [@jpastoor].
- Error details from failed API calls were not available back from `Api::api method` call by [@betterphp].
- Warning about `count()` function usage on PHP 7.2, when searching for issues by [@aik099].
- Capitalize `globalId` properly in `createRemotelink` [@glensc].
- The `Api::getIssueTypes` was always returning an error by [@aik099].

## [1.0.0] - 2014-07-27
### Added
- Initial release.

[Unreleased]: https://github.com/chobie/jira-api-restclient/compare/v1.0.0...HEAD
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
