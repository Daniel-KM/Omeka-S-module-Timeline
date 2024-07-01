jQuery(document).ready(function($) {

    const kTimeline = {
        loadTimeline: function(timelineId, timelineDataUrl, timelineParams) {
            $.getJSON(timelineDataUrl, function(data) {
                var tl = new TL.Timeline(timelineId, data, timelineParams)
                window.timelinesKnightlab[timelineId] = tl;
            });
        },

        /**
         * @todo Remove these checks and preparation and do like exhibit.
         */
        checkAndLoadTimeline: function(timelineId, timelineDataUrl, timelineParams) {
            $.getJSON(timelineDataUrl, function(data) {
                function parseDate(entryDateString) {
                    var entryDate = entryDateString;
                    var parsedDate = entryDate.split('-');
                    var entryYear = parsedDate[0];
                    var entryMonth = parsedDate[1];
                    var entryDay = parsedDate[2].slice(0, 2);
                    return [entryYear, entryMonth, entryDay];
                };

                // console.log('data ', data);
                var timelineEvents = new Array();

                for (var i = 0; i < data.events.length; i++) {
                    // Parse the date string into Y, M, D.
                    // Assumes YYYY-MM-DD.
                    var startDate = parseDate(data.events[i].start);

                    // Create the slide object for the record.
                    var timelineEntry = {
                        "text": {
                            "headline": "<a href=" + data.events[i].link + ">" + data.events[i].title + "</a>"
                        },
                        "start_date": {
                            "year": startDate[0],
                            "month": startDate[1],
                            "day": startDate[2]
                        },
                    };

                    // If the item has a description, include it.
                    if (data.events[i].description) {
                        timelineEntry.text["text"] = data.events[i].description;
                    }

                    // If the record has an end date, include it.
                    if (data.events[i].end) {
                        var endDate = parseDate(data.events[i].end);
                        timelineEntry["end_date"] = {
                            "year": endDate[0],
                            "month": endDate[1],
                            "day": endDate[2]
                        };
                    }

                    // If the record has a file attachment, include that.
                    // Limits based on returned JSON:
                    // If multiple images are attached to the record, it only shows the first.
                    // If a pdf is attached, it does not show it or indicate it.
                    // If an mp3 is attached in Files, it does not appear.
                    if (data.events[i].image) {
                        timelineEntry["media"] = { "url": data.events[i].image };
                    }

                    // Add the slide to the events.
                    timelineEvents.push(timelineEntry);
                }

                // Create the collection of slides.
                var slides = {
                    "title": {
                        "text": {
                            "headline": '',
                            "text": ''
                        }
                    },
                    "events": timelineEvents
                };

                // Initialize the timeline instance.
                var tl = new TL.Timeline(timelineId, slides, timelineParams);

                window.timelinesKnightlab[timelineId] = tl;
            });
        },
    };

    // The config is defined inside the html.
    if (typeof timelines === 'undefined') {
        return;
    }

    if (typeof window.timelinesKnightlab === 'undefined') {
        window.timelinesKnightlab = {};
    }

    Object.entries(timelines).forEach(([tid, conf]) => {
        if (conf.type === 'knightlab' && conf.id && conf.url) {
            // For exhibit, the conf is already checked.
            if (conf.isExhibit) {
                kTimeline.loadTimeline(conf.id, conf.url, conf.params);
            } else {
                kTimeline.checkAndLoadTimeline(conf.id, conf.url, conf.params);
            }
        }
    })

});
