Timeline (module for Omeka S)
=============================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[![Build Status](https://travis-ci.org/Daniel-KM/Omeka-S-module-Timeline.svg?branch=develop,master)](https://travis-ci.org/Daniel-KM/Omeka-S-module-Timeline)

[Timeline] is a module for [Omeka S] that integrates the [SIMILE Timeline]
widget and the online [Knightlab timeline] to create timelines via page blocks
and resource blocks.


Installation
------------

See general end user documentation for [installing a module].

The module uses an external library to allow to load timeline from a standard
spreadsheet, so use the release zip to install it, or use and init the source.

* From the zip

Download the last release [Timeline.zip] from the list of releases (the master
does not contain the dependency), and uncompress it in the `modules` directory.

* From the source and for development:

If the module was installed from the source, rename the name of the folder of
the module to `Timeline`, and go to the root module, and run:

```sh
composer install --no-dev
```

Then install it like any other Omeka module and follow the config instructions.

Note: If Omeka is https, if external assets are used, and if the Simile library
is used, the library will not load on recent browsers, because the online
library contains an url with unsecure http. In that case, you need to set the
option "Use Internal library for Simile", that is the default anyway.


Usage
-----

Once enabled, the module adds new block for site pages and resource pages.

For resource pages, there are two blocks for item set: with the internal widget
Simile or with the third party Knightlab. Simply set them in the config of the
theme. The options used are the default one of the modules, that may be modified
via the main config file of Omeka (`config/local.config.php`) (key ['timeline']['block_settings']['timeline']).

For site pages, the first block allows to create an automatic timeline and the
second one allows to create an exhibit with selected items. Simply select one of
them and config it (the item pool and eventually the options).

Furthermore, any timeline can be created dynamically via the api `/api/timeline`.

The options for the blocks are the following ones.

### Fields

Choose which fields you want the module to use on the timeline by default.

* Item Title: The field you would like displayed for the item’s title in its
  information bubble. The default is `dcterms:title`.
* Item Description: The field you would like displayed for the item’s
  description in its information bubble. The default is `dcterms:description`.
* Item Date: The field you would like to use for item dates on the timeline.
  The default is `dcterms:date`.
* Item Date End: The field you would like to use for item end dates on the
  timeline. It is useless if dates are ranges (see below) or if you don’t have
  end date.
* Render Year: Date entered as a single number, like `1066`, can be skipped,
  plotted as a single event or marked as a full year.
* Center Date: The date that is displayed by the viewer when loaded. It can
  be any date with the format `YYYY-MM-DD`. An empty string means now, a
  `0000-00-00` the earliest date and `9999-99-99` the latest date.
* Viewer: The raw json parameters for the viewer, for example to display only
  one row, or to change the scale.

All these parameters can be customized for each timeline.

### Url for the dynamic json

The json is available at "/api/timeline?block-id=xxx". This url supports any
dynamic standard item query too if you want to get the json without a block.
The old route "timeline-block" (for url "/timeline/:block-id/events.json") was
deprecated and removed in a version 3.4.22.

By default, the timeline is formatted for Simile. To get the Knightlab format,
append `?output=knightlab` to the query.

### Add a Timeline Block or an Timeline Exhibit

Creating a timeline is a two-step process:

1. From the admin panel, edit a page, then click the "Timeline" button in the
  list of new blocks to add.

2. To choose which items appear on your timeline, fill the "Item Pool" form. The
  options are the same than in the config by default (see above).

  ![Timeline Block](https://gitlab.com/Daniel-KM/Omeka-S-module-Timeline/blob/master/data/readme/timeline-block-v3-4.png)

Ready! Open the page.

  ![Timeline Page](https://gitlab.com/Daniel-KM/Omeka-S-module-Timeline/blob/master/data/readme/timeline-page-v3-4.png)

**Important**: The number of items should be limited according to the memory of
the server: currently, the json output is created in one shot, so it can't
manage more than some dozens or hundreds of items.

### Dates for Items

Timeline will attempt to convert the value for a date string into an [ISO 8601]
date format. Some example date values you can use:

  * `January 1, 2012`
  * `2012-01-01`
  * `1 Jan 2012`
  * `2012-12-15`

To denote spans of time, separate the start and end date with a `/`:

  * `January 1, 2012/February 1, 2012`

A common format is managed too:

  * `1939-1945`

It must be `1939/1945` to be compatible with the standard ISO 8601.

Timeline handles dates with years shorter than 4 digits. For these you may need
to pad the years with enough zeros to make them have four digits. For example,
`476` should be written `0476`.

Also, you can enter in years before common era by putting a negative sign before
the year. If the date has less than four digits, you’ll also need to add extra
zeros.

So here are some more examples of dates.

  * `0200-01-01`
  * `0002-01-01`
  * `-0002-01-01`
  * `-2013-01-01`

When a date is a single number, like `1066`, a parameter in the config page
allows to choose its rendering:

  * skip the record (default)
  * 1st January
  * 1st July
  * full year (range period)

This parameter applies with a range of dates too, for example `1939/1945`.

In all cases, it’s recommended to follow the standard [ISO 8601] as much as
possible and to be as specific as possible.

### Parameters of the viewer

Some parameters of the viewer may be customized for each timeline.

#### Simile timeline

Currently, only the `CenterDate` and the `bandInfos` are managed for the Simile
timeline. The default is automatically included when the field is empty.

```javascript
{
    "centerDate": "1000-01-01",
    "bandInfos": [
        {
            "width": "80%",
            "intervalUnit": Timeline.DateTime.MONTH,
            "intervalPixels": 100,
            "zoomIndex": 10,
            "zoomSteps": new Array(
                {"pixelsPerInterval": 280, "unit": Timeline.DateTime.HOUR},
                {"pixelsPerInterval": 140, "unit": Timeline.DateTime.HOUR},
                {"pixelsPerInterval": 70, "unit": Timeline.DateTime.HOUR},
                {"pixelsPerInterval": 35, "unit": Timeline.DateTime.HOUR},
                {"pixelsPerInterval": 400, "unit": Timeline.DateTime.DAY},
                {"pixelsPerInterval": 200, "unit": Timeline.DateTime.DAY},
                {"pixelsPerInterval": 100, "unit": Timeline.DateTime.DAY},
                {"pixelsPerInterval": 50, "unit": Timeline.DateTime.DAY},
                {"pixelsPerInterval": 400, "unit": Timeline.DateTime.MONTH},
                {"pixelsPerInterval": 200, "unit": Timeline.DateTime.MONTH},
                {"pixelsPerInterval": 100, "unit": Timeline.DateTime.MONTH} // DEFAULT zoomIndex
            )
        },
        {
            "overview": true,
            "width": "20%",
            "intervalUnit": Timeline.DateTime.YEAR,
            "intervalPixels": 200
        }
    ]
}
```

For Simile, these constants can be used:

```js
SimileAjax.DateTime.MILLISECOND    = 0;
SimileAjax.DateTime.SECOND         = 1;
SimileAjax.DateTime.MINUTE         = 2;
SimileAjax.DateTime.HOUR           = 3;
SimileAjax.DateTime.DAY            = 4;
SimileAjax.DateTime.WEEK           = 5;
SimileAjax.DateTime.MONTH          = 6;
SimileAjax.DateTime.YEAR           = 7;
SimileAjax.DateTime.DECADE         = 8;
SimileAjax.DateTime.CENTURY        = 9;
SimileAjax.DateTime.MILLENNIUM     = 10;

SimileAjax.DateTime.EPOCH          = -1;
SimileAjax.DateTime.ERA            = -2;
```

If wanted, in order to use a strict json array in the config, without constants,
you should replace them by the matching integer.

#### Knightlab timeline

You can find all the available parameters in the [Knightlab timeline documentation].

Notes:
- When a field is not filled, the properties of the resource are used (title,
  description, creator).
- Date should be ISO-8601, partial ("YYYY", etc.) or full ("YYYY-MM-DDT00:00:00Z").
  Let blank to use the date of the attachment.
- The main display date override start and end dates in some places.
- The resource can be an item id, a media id, or any other resource id.
- If the resource is not set, it’s possible to use an external content,
  generally an url, but the viewer supports some other content.
- Events are automatically sorted.

#### Knightlab timeline with a file

The views of the exhibit can be filled with a [spreadsheet formatted as indicated].
You should set the filename in the input field. It can be a locally loaded file
via [Easy Admin] or any url.

The format should be ods (standard OpenDocument Spreadsheet), csv or tsv
(tab separated values). If you use, csv or tsv, the file should be utf8 encoded
(unicode) and there should not be line jump in any cell for now. That's why ods
is the recommended format, allowing cells and descriptions with multiple lines.
The format "ods" is supported only if the php version is at least 8.1.

For columns Media and Background, it is possible the numeric internal id of the
resource, a string like `asset/xxx` to use an asset, or a valid external
absolute url. It may be an item identifier (Dublin Core) too.

If the Media is a resource, the columns date, title and description will be
automatically filled if empty.

The page should be saved to regenerate slides.

**Warning**: When a valid file is uploaded, all existing slides will be
replaced.

### Modifying the block template for Timeline

Before Omeka S v4.1, copy it in your theme (file `view/common/block-layout/timeline.phtml`).

Since Omeka S v4.1, copy the file `view/common/block-layout/timeline.phtml` in
the theme directory for block templates and rename it as you want, for example
`view/common/block-template/timeline-xxx.phtml`, then declare it in the file `config/theme.ini`,
for example here `block_templates.timeline.xxx = "Custom xxx", and finally
select it in the block layout in the admin site page edition.

### Modifying the viewer

The template file used to load the timeline is `asset/js/timeline.js`.

You can copy it in your `themes/my_theme/asset/js` folder to customize it. The
same for the default css. See the main [wiki], an [example of use] with Neatline
for Omeka Classic, and the [examples] of customization on the wiki.


TODO
----

- [ ] Create a view helper and restructure the blocks.
- [ ] Integrate attachments for the exhibit and improve the form (hide all by default except resource),
- [ ] Integrate Numeric data type Interval and Duration (?).
- [ ] Create the json for knightlab directly from the controller, not the js in view.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

This module is published under the [CeCILL v2.1] license, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software's author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user's attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software's suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.

The module uses the widget [SIMILE Timeline], published under the license MIT.
See files in `asset/vendor` for more info.


Copyright
---------

### Module

* Copyright The Board and Visitors of the University of Virginia, 2010–2012
* Copyright Daniel Berthereau, 2016-2025 (see [Daniel-KM] on GitLab)

### Translations

* Martin Liebeskind (German)
* Gillian Price (Spanish)
* Oguljan Reyimbaeva (Russian)
* Katina Rogers (French)

This [Omeka S] module is a full rewrite of the [fork of NeatlineTime plugin] for
[Omeka Classic]. The original NeatlineTime plugin was created by the [Scholars’ Lab]
at the University of Virginia Library and improved by various authors. Some
improvements were integrated for the digital library of [Saint-Quentin Art & Histoire]
and the [Curiothèque] of the [Institut Curie].


[Timeline]: https://gitlab.com/Daniel-KM/Omeka-S-module-Timeline
[Omeka S]: https://omeka.org/s
[Scholars’ Lab]: http://scholarslab.org
[fork of NeatlineTime plugin]: https://gitlab.com/Daniel-KM/Omeka-plugin-NeatlineTime
[Omeka Classic]: http://omeka.org
[SIMILE Timeline]: http://www.simile-widgets.org/wiki/Timeline
[wiki]: http://www.simile-widgets.org/wiki/Timeline
[ISO 8601]: http://www.iso.org/iso/home/standards/iso8601.htm
[Knightlab timeline]: https://timeline.knightlab.com
[Knightlab timeline documentation]: https://timeline.knightlab.com/docs/options.html
[spreadsheet formatted as indicated]: https://timeline.knightlab.com/docs/using-spreadsheets.html
[example of use]: https://docs.neatline.org/working-with-the-simile-timeline-widget.html
[examples]: http://www.simile-widgets.org/timeline/examples/index.html
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-Timeline/-/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[themeing-plugin-pages]: http://omeka.org/codex/Theming_Plugin_Pages "Theming Plugin Pages"
[Scholars’ Lab]: https://github.com/scholarslab
[Saint-Quentin Art & Histoire]: http://saintquentinartethistoire.fr
[Curiothèque]: https://curiotheque.musee.curie.fr/
[Institut Curie]: https://curie.fr/
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
