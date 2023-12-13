# Editor widgets
Collection of dashboard widgets designed for TYPO3 editors

## Widgets
### Storage usage
Shows the used storage size in percent from the configured maximum.

![Preview of Storage usage widget](Documentation/Images/storageSize.png)

### Biggest unused files
Shows the top 10 biggest files that are not used.
(Entries from sys_file that do not have an entry in sys_refindex)

![Preview of Biggest unused files widget](Documentation/Images/unusedFiles.png)

### Duplicate files
Shows a list of suspected, duplicated files based on the sha1-hash from sys_file.

![Preview of Duplicate files widget](Documentation/Images/duplicateFiles.png)

### Last changed pages
Simply a list of the last 10 edited pages that are accessible by the current backend user.

![Preview of Last changed pages widget](Documentation/Images/lastChangedPages.png)

### Latest redirects
The last 10 updated redirects.

![Preview of Latest redirects widget](Documentation/Images/latestRedirects.png)

### Broken links
Shows a list of all broken links with page, element, target and error message.

**Please note:** For this widget the core extension typo3/cms-linkvalidator must be installed and active as the list is taken directly from the LinkValidator table.

![Preview of Broken links widget](Documentation/Images/brokenLinks.png)


## Installation
Use composer to add the extension:
```
composer require "sitegeist/editor-widgets"
```
* Flush Caches
* Configure the max size for the default storage

## Configuration
### Storage size

This extension adds a field to the storage record to configure the desired maximum storage size.
Go to `List => [ROOT] => File Storage` and edit the default storage (normally, this should be the fileadmin).
There is a new tab called "Editor Widgets" with a new field where you can enter a value in bytes or simply KB, MB, GB.

**Please note:** This value is only used for displaying how much space is used. There is no real limit.


## Special thanks
*The development and the public-releases of this package is generously sponsored
by SPIEGEL-Verlag Rudolf Augstein GmbH & Co. KG*
## Authors & Sponsors
* SPIEGEL-Verlag Rudolf Augstein GmbH & Co. KG
* [Benjamin Tammling](https://github.com/Atomschinken)
* [Ulrich Mathes](https://github.com/ulrichmathes)
* [All contributors](https://github.com/sitegeist/editor-widgets/graphs/contributors)
