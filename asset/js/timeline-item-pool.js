// Merged "advanced-search.js" and "site-item-pool.js" to allow multiple
// subforms for item pool.

(function ($) {
    /**
     * Prepare the advanced search form.
     */
    function prepareAdvancedSearch(block) {
        var advancedSearch = $(block).find('#advanced-search');

        // Remove all names from query form elements.
        $(block).find('.query-type, .query-text, .query-property').attr('name', null);

        // Add a value.
        advancedSearch.on('click', '.add-value', function(e) {
            e.preventDefault();
            var first = $(this).parents('.field').find('.value').first();
            var clone = first.clone();
            clone.children('input[type="text"]').val(null);
            clone.children('select').prop('selectedIndex', 0);
            clone.insertBefore($(this));
        });

        // Remove a value.
        advancedSearch.on('click', '.remove-value', function(e) {
            e.preventDefault();
            var values = $(this).parents('.inputs').children('.value');
            $(this).parent('.value').remove();
        });

        // Bypass regular form handling for value, property, and has property queries.
        advancedSearch.submit(function(event) {
            $(block).find('#property-queries').find('.value').each(function(index) {
                var text = $(this).children('.query-text');
                if (!$.trim(text.val())) {
                    return; // do not process an empty query
                }
                var propertyVal = $(this).children('.query-property').val();
                if (!$.isNumeric(propertyVal)) {
                    propertyVal = 0;
                }
                var type = $(this).children('.query-type');
                $('<input>').attr('type', 'hidden')
                    .attr('name', 'property[' + propertyVal + '][' + type.val() + '][]')
                    .val(text.val())
                    .appendTo(advancedSearch);
            });

            $(block).find('#has-property-queries').find('.value').each(function(index) {
                var property = $(this).children('.query-property');
                if (!$.isNumeric(property.val())) {
                    return; // do not process an invalid property
                }
                var type = $(this).children('.query-type');
                $('<input>').attr('type', 'hidden')
                    .attr('name', 'has_property[' + property.val() + ']')
                    .val(type.val())
                    .appendTo(advancedSearch);
            });
        });
    }

    /**
     * Extract the item pool query
     */
    function extractItemPoolQuery(block) {
        var query = {};

        // Handle the resource class
        var resourceClassId = $(block).find('#advanced-search select[name="resource_class_id"]').val();
        if (resourceClassId) {
            query['resource_class_id'] = Number(resourceClassId);
        }

        // Handle the item sets
        $(block). find('#item-sets').find('select[name="item_set_id[]"] option:selected').each(function(index) {
            var itemSetId = $(this).val();
            if (itemSetId) {
                if (!query.hasOwnProperty('item_set_id')) {
                    query['item_set_id'] = [];
                }
                query['item_set_id'].push(Number(itemSetId));
            }
        });

        // Handle the property queries
        $(block).find('#property-queries').find('.value').each(function(index) {
            var textVal = $(this).children('.query-text').val();
            if (!$.trim(textVal)) {
                return; // do not process an empty query
            }
            var propertyVal = $(this).children('.query-property').val();
            if (!$.isNumeric(propertyVal)) {
                propertyVal = 0;
            }
            if (!query.hasOwnProperty('property')) {
                query['property'] = {};
            }
            if (!query.property.hasOwnProperty(propertyVal)) {
                query.property[propertyVal] = {};
            }
            var typeVal = $(this).children('.query-type').val();
            if (!query.property[propertyVal].hasOwnProperty(typeVal)) {
                query.property[propertyVal][typeVal] = [];
            }
            query.property[propertyVal][typeVal].push(textVal);
        });

        // Handle the has_property queries
        $(block).find('#has-property-queries').find('.value').each(function(index) {
            var propertyVal = $(this).children('.query-property').val();
            if (!$.isNumeric(propertyVal)) {
                return; // do not process an invalid property
            }
            if (!query.hasOwnProperty('has_property')) {
                query['has_property'] = {};
            }
            var typeVal = $(this).children('.query-type').val();
            query.has_property[propertyVal] = Number(typeVal);
        });

        return JSON.stringify(query);
    }

    $(document).ready(function () {
        // Handle events on the advanced search form for item pool of added blocks.
        $('#blocks').find('.block[data-block-layout="timeline"]').each(function() {
            prepareAdvancedSearch(this);
        });

        $('#blocks').on('o:block-added', '.block[data-block-layout="timeline"]', function(e) {
            $(this).filter('.block[data-block-layout="timeline"]').each(function() {
                prepareAdvancedSearch(this);
            });
        });

        // TODO Remove the fix for <= Omeka S beta 3 (no id "site-page-form").
        $('#site-page-form, .site-pages #content form').on('submit', function(e) {
             $('#site-page-form .block.value, .site-pages #content form.block.value').each(function(blockIndex) {
                 if ($(this).data('block-layout') == 'timeline' && !$(this).hasClass('delete')) {
                     $('<input>', {type: 'hidden', name: 'o:block[' + blockIndex.toString() + '][o:data][item_pool]'})
                         .val(extractItemPoolQuery(this))
                         .appendTo($(this));
                 }
             });
        });
    });
})(jQuery);
