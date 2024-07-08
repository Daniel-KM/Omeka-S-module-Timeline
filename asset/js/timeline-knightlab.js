jQuery(document).ready(function($) {

    if (typeof TL === 'undefined') {
        console.log('The timeline library should be loaded first.');
        return;
    }

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
                // Initialize the timeline instance.
                var slides = data;
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
