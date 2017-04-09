Timeline (module for Omeka S)
=============================

[![Build Status](https://travis-ci.org/Daniel-KM/Omeka-S-module-Timeline.svg?branch=develop,master)](https://travis-ci.org/Daniel-KM/Omeka-S-module-Timeline)

[Timeline] is a module for [Omeka S] that integrates the [SIMILE Timeline]
plugin and the [Knightlab timeline] to create timelines.

This [Omeka S] module is a full rewrite of the [fork of NeatlineTime plugin] for
[Omeka Classic]. The original NeatlineTime plugin was created by the [Scholars’ Lab]
at the University of Virginia Library.


Installation
------------

Uncompress files in the module directory and rename module folder `EasyInstall`.

Then install it like any other Omeka module and follow the config instructions.

Configure the plugin to choose which fields you want the plugin to use on the
timeline by default.

* Item Title: The field you would like displayed for the item’s title in its
  information bubble. The default is `dcterms:title`.
* Item Description: The field you would like displayed for the item’s
  description in its information bubble. The default is `dcterms:description`.
* Item Date: The field you would like to use for item dates on the   timeline.
  The default is `dcterms:date`.
* Render Year: Date entered as a single number, like "1066", can be skipped,
  plotted as a single event or marked as a full year.
* Center Date: The date that is displayed by the viewer when loaded. It can
  be any date with the format (YYYY-MM-DD). An empty string means now, a
  "0000-00-00" the earliest date and "9999-99-99" the latest date.

All these parameters can be customized for each timeline.


Usage
-----

Once enabled, the module adds a tab to the Omeka S admin panel. From here, you
can browse existing timelines, and add, edit, and delete timelines.

Uninstalling the plugin will only remove timelines added to the Omeka S archive,
not any items displayed on those timelines.

### Add a Timeline

Creating a timeline is a two-step process:

1. From the admin → Timeline page, click the "Add New Timeline" button to begin
  creating a timeline.

  ![Browse Timelines](http://neatline.org/wp-content/uploads/2014/01/neatlinetime-browse.png)

2. Give your timeline a title and description, and choose whether you wish to
  make the timeline public and featured. Save your changes.

  ![Add a Timeline Form](http://neatline.org/wp-content/uploads/2014/01/neatlinetime-add-timeline.png)

3. To choose which items appear on your timeline, click the "Edit Query" link
  beside your existing timeline.

  ![Edit Query Link](http://neatline.org/wp-content/uploads/2014/01/neatlinetime-timeline-saved.png)

4. This will take you to a form similar to Omeka S’ advanced search form. From
  here, you can perform a search for any items in your archive, and if those
  items contain a valid date in their Dublin Core:Date field, they will be
  displayed on the timeline.

  ![Edit Query](http://neatline.org/wp-content/uploads/2014/01/neatlinetime-item-query.png)

5. With a query defined, the matching items will be rendered on the timeline:

  ![Timeline](http://neatline.org/wp-content/uploads/2014/01/neatlinetime-admin-show.png)

### Dates for Items

Timeline will attempt to convert the value for a date string into an [ISO-8601]
date format. Some example date values you can use:

  * January 1, 2012
  * 2012-01-01
  * 1 Jan 2012
  * 2012-12-15

To denote spans of time, separate the start and end date with a "`/`":

  * January 1, 2012/February 1, 2012

Timeline handles dates with years shorter than 4 digits. For these you’ll need
to pad the years with enough zeros to make them have four digits. For example,
"`476`" should be written "`0476`".

Also, you can enter in years before common era by putting a negative sign before
the year. If the date has less than four digits, you’ll also need to add extra
zeros.

So here are some more examples of dates.

  * 0200-01-01
  * 0002-01-01
  * -0002-01-01
  * -2013-01-01

When a date is a single number, like "1066", a parameter in the config page
allows to choose its rendering:

  * skip the record (default)
  * 1st January
  * 1st July
  * full year (range period)

This parameter applies with a range of dates too, for example "1939/1945".

In all cases, it’s recommended to follow the standard [ISO 8601] as much as
possible and to be as specific as possible.

### Parameters of the viewer

Some parameters of the viewer may be customized for each timeline. Currently,
only the `CenterDate` and the `bandInfos` are managed for the Simile timeline.
The default is automatically included when the field is empty.

```javascript
{
bandInfos:
    [
        {
            width: "80%",
            intervalUnit: Timeline.DateTime.MONTH,
            intervalPixels: 100,
            zoomIndex: 10,
            zoomSteps: new Array(
                {pixelsPerInterval: 280, unit: Timeline.DateTime.HOUR},
                {pixelsPerInterval: 140, unit: Timeline.DateTime.HOUR},
                {pixelsPerInterval: 70, unit: Timeline.DateTime.HOUR},
                {pixelsPerInterval: 35, unit: Timeline.DateTime.HOUR},
                {pixelsPerInterval: 400, unit: Timeline.DateTime.DAY},
                {pixelsPerInterval: 200, unit: Timeline.DateTime.DAY},
                {pixelsPerInterval: 100, unit: Timeline.DateTime.DAY},
                {pixelsPerInterval: 50, unit: Timeline.DateTime.DAY},
                {pixelsPerInterval: 400, unit: Timeline.DateTime.MONTH},
                {pixelsPerInterval: 200, unit: Timeline.DateTime.MONTH},
                {pixelsPerInterval: 100, unit: Timeline.DateTime.MONTH} // DEFAULT zoomIndex
            )
        },
        {
            overview: true,
            width: "20%",
            intervalUnit: Timeline.DateTime.YEAR,
            intervalPixels: 200
        }
    ]
}
```


### Browsing timelines

You can browse existing timelines by clicking on the "Browse Timelines" from
your public theme, or the "Timeline" tab in the admin panel.

### Viewing specific timelines

You can always see your timeline by click the title of the timeline in the
admin. The URL for your timelines will be `timeline/:id`, where `:id` is the ID
number for your timeline.

  ![Public Show](http://neatline.org/wp-content/uploads/2014/01/neatlinetime-public-show.png)

### Modifying theme templates for Timeline

Timeline contains theme templates that control how its various pages are
displayed in your public theme. As with other Omeka S modules, you can override
these by copying it in your theme.

The template files available in Timeline include:

* timeline/browse.php - The template for browsing existing timelines.
* timeline/show.php - The template for showing a specific timeline.

### Modifying the viewer

The template file used to load the timeline is `asset/js/timeline.js`.

You can copy it in your `themes/my_theme/asset/js` folder to customize it. The
same for the default css. See the main [wiki], an [example of use] with Neatline
for Omeka Classic, and the [examples] of customization on the wiki.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitHub.


License
-------

This module is published under the [CeCILL v2.1] licence, compatible with
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
See files in `asset/js` for more info.


Contacts
--------

* Daniel Berthereau (see [Daniel-KM] on GitHub)


Copyright
---------

### Module

* Copyright The Board and Visitors of the University of Virginia, 2010–2012
* Copyright Daniel Berthereau, 2016-2017

### Translations

* Martin Liebeskind (German)
* Gillian Price (Spanish)
* Oguljan Reyimbaeva (Russian)
* Katina Rogers (French)


[Timeline]: https://github.com/Daniel-KM/Omeka-S-module-Timeline
[Omeka S]: https://omeka.org/s
[Scholars’ Lab]: http://scholarslab.org
[Omeka Classic]: http://omeka.org
[SIMILE Timeline]: http://www.simile-widgets.org/wiki/Timeline
[wiki]: http://www.simile-widgets.org/wiki/Timeline
[ISO 8601]: http://www.iso.org/iso/home/standards/iso8601.htm
[Knightlab timeline]: https://timeline.knightlab.com
[example of use]: https://docs.neatline.org/working-with-the-simile-timeline-widget.html
[examples]: http://www.simile-widgets.org/timeline/examples/index.html
[module issues]: https://github.com/Daniel-KM/Omeka-S-module-Timeline/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[themeing-plugin-pages]: http://omeka.org/codex/Theming_Plugin_Pages "Theming Plugin Pages"
[ScholarLab]: https://github.com/scholarslab "Scholar’s Lab"
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
