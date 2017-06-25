/**
 * Manage events for timeline blocks.
 */
(function ($) {
    /**
     * Prepare the advanced search form inside a block for item pool.
     *
     * Light adaptation of the "advanced-search.js" of Omeka S.
     */
    function initItemPool(block) {
        var advancedSearch = $(block).find('#advanced-search');

        var values = advancedSearch.find('#property-queries .value');
        var index = values.length;

        // Add a value for properties.
        advancedSearch.find('#property-queries').on('click', '.add-value', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var first = values.first();
            var clone = first.clone();
            clone.children('input[type="text"]').val(null).prop('disabled', false);
            clone.children('select').prop('selectedIndex', 0);
            clone.children(':input').attr('name', function () {
                return this.name.replace(/\[\d\]/, '[' + index + ']');
            });
            clone.insertBefore($(this));
            index++;
        });

        // Add a value for item sets.
        advancedSearch.find('#item-sets').on('click', '.add-value', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var first = $(this).parents('.field').find('.value').first();
            var clone = first.clone();
            clone.children('select').prop('selectedIndex', 0);
            clone.insertBefore($(this));
        });

        // Remove a value.
        advancedSearch.find('#property-queries, #item-sets').on('click', '.multi-value .remove-value', function(e) {
            e.preventDefault();
            $(this).closest('.value').remove();
        });

        function disableQueryTextInput() {
            var queryType = $(this);
            var queryText = queryType.siblings('.query-text');
            if (queryType.val() === 'ex' || queryType.val() === 'nex') {
                queryText.prop('disabled', true);
            } else {
                queryText.prop('disabled', false);
            }
        }

        advancedSearch.find('.query-type').each(disableQueryTextInput);
        advancedSearch.on('change', '.query-type', disableQueryTextInput);
    }

    /**
     * Manage the array of input of the block.
     *
     * This method is needed, because the advanced search is not designed to be
     * used in a block.
     */
    function finalizeInputBlock(block, blockIndex) {
        var selectors = $(block).find('#advanced-search');

        // Fix all name fields so all fields are managed in a full array.
        $(block).find('#advanced-search :input').each(function() {
            var name = $(this).attr('name');
            if (typeof name === 'undefined') {
                return;
            }
            if (name.indexOf('[') === -1) {
                $(this).attr('name', '[' + name + ']');
            }
            // Replace "property[0]" by "[property][0]", etc.
            else {
                $(this).attr('name', name
                    .replace(/^([\w-]+?)(\[.*)$/g, '[$1]$2')
                    .replace(/^(.*\])([\w-]+?)$/g, '$1[$2]'));
            }

            $(this).attr('name', 'o:block[__blockIndex__][o:data][item_pool]' + $(this).attr('name'));
        });

        // Rename other fields according to the block.
        $(block).find(':input ').each(function() {
            var name = $(this).attr('name');
            if (typeof name === 'undefined') {
                return;
            }
            $(this).attr('name', name.replace('__blockIndex__', blockIndex.toString()));
        });
    }

    $(document).ready(function () {
        // Handle events on the advanced search form for item pool of existing blocks.
        $('#blocks').find('.block[data-block-layout="timeline"]').each(function() {
            initItemPool(this);
        });

        // Bind events on the advanced search form for item pool of added blocks.
        $('#blocks').on('o:block-added', '.block[data-block-layout="timeline"]', function(e) {
            $(this).filter('.block[data-block-layout="timeline"]').each(function() {
                initItemPool(this);
            });
        });

        // Manage the name of the form input elements.
        $('#site-page-form').on('submit', function(e) {
            $('#site-page-form .block.value').each(function(blockIndex) {
                if ($(this).hasClass('delete')) {
                    $(this).find(':input').remove();
                } else {
                    finalizeInputBlock(this, blockIndex);
                }
            });
        });
    });
})(jQuery);
