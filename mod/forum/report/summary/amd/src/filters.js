// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Module responsible for handling forum summary report filters.
 *
 * @module     forumreport_summary/filters
 * @package    forumreport_summary
 * @copyright  2019 Michael Hawkins <michaelh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Popper from 'core/popper';
import CustomEvents from 'core/custom_interaction_events';
import Selectors from 'forumreport_summary/selectors';
import Y from 'core/yui';
import Ajax from 'core/ajax';

export const init = (root) => {
    let jqRoot = $(root);

    // Hide loading spinner and show report once page is ready.
    // This ensures filters can be applied when sorting by columns.
    $(document).ready(function() {
        $('.loading-icon').hide();
        $('#summaryreport').removeClass('hidden');
    });

    // Generic filter handlers.

    // Called to override click event to trigger a proper generate request with filtering.
    const generateWithFilters = (event) => {
        let newLink = $('#filtersform').attr('action');

        if (event) {
            event.preventDefault();

            let filterParams = event.target.search.substr(1);
            newLink += '&' + filterParams;
        }

        $('#filtersform').attr('action', newLink);
        $('#filtersform').submit();
    };

    // Override 'reset table preferences' so it generates with filters.
    $('.resettable').on("click", "a", function(event) {
        generateWithFilters(event);
    });

    // Override table heading sort links so they generate with filters.
    $('thead').on("click", "a", function(event) {
        generateWithFilters(event);
    });

    // Override pagination page links so they generate with filters.
    $('.pagination').on("click", "a", function(event) {
        generateWithFilters(event);
    });

    // Submit report via filter
    const submitWithFilter = (containerelement) => {
        // Close the container (eg popover).
        $(containerelement).addClass('hidden');

        // Submit the filter values and re-generate report.
        generateWithFilters(false);
    };

    // Use popper to override date mform calendar position.
    const updateCalendarPosition = (referenceid) => {
        let referenceElement = document.querySelector(referenceid),
            popperContent = document.querySelector(Selectors.filters.date.calendar);

        popperContent.style.removeProperty("z-index");
        new Popper(referenceElement, popperContent, {placement: 'bottom'});
    };

    // Call when opening filter to ensure only one can be activated.
    const canOpenFilter = (event) => {
        if (document.querySelector('[data-openfilter="true"]')) {
            return false;
        }

        event.target.setAttribute('data-openfilter', "true");
        return true;
    };

    // Groups filter specific handlers.

    // Event handler for clicking select all groups.
    jqRoot.on(CustomEvents.events.activate, Selectors.filters.group.selectall, function() {
        let deselected = root.querySelectorAll(Selectors.filters.group.checkbox + ':not(:checked)');
        deselected.forEach(function(checkbox) {
            checkbox.checked = true;
        });
    });

    // Event handler for clearing filter by clicking option.
    jqRoot.on(CustomEvents.events.activate, Selectors.filters.group.clear, function() {
        // Clear checkboxes.
        let selected = root.querySelectorAll(Selectors.filters.group.checkbox + ':checked');
        selected.forEach(function(checkbox) {
            checkbox.checked = false;
        });
    });

    // Event handler for showing groups filter popover.
    jqRoot.on(CustomEvents.events.activate, Selectors.filters.group.trigger, function(event) {
        if (!canOpenFilter(event)) {
            return false;
        }

        // Create popover.
        let referenceElement = root.querySelector(Selectors.filters.group.trigger),
            popperContent = root.querySelector(Selectors.filters.group.popover);

        new Popper(referenceElement, popperContent, {placement: 'bottom'});

        // Show popover.
        popperContent.classList.remove('hidden');

        // Change to outlined button.
        referenceElement.classList.add('btn-outline-primary');
        referenceElement.classList.remove('btn-primary');

        // Let screen readers know that it's now expanded.
        referenceElement.setAttribute('aria-expanded', true);
        return true;
    });

    // Event handler to click save groups filter.
    jqRoot.on(CustomEvents.events.activate, Selectors.filters.group.save, function() {
        submitWithFilter('#filter-groups-popover');
    });

    // Dates filter specific handlers.

   // Event handler for showing dates filter popover.
    jqRoot.on(CustomEvents.events.activate, Selectors.filters.date.trigger, function(event) {
        if (!canOpenFilter(event)) {
            return false;
        }

        // Create popover.
        let referenceElement = root.querySelector(Selectors.filters.date.trigger),
            popperContent = root.querySelector(Selectors.filters.date.popover);

        new Popper(referenceElement, popperContent, {placement: 'bottom'});

        // Show popover and move focus.
        popperContent.classList.remove('hidden');
        popperContent.querySelector('[name="filterdatefrompopover[enabled]"]').focus();

        // Change to outlined button.
        referenceElement.classList.add('btn-outline-primary');
        referenceElement.classList.remove('btn-primary');

        // Let screen readers know that it's now expanded.
        referenceElement.setAttribute('aria-expanded', true);
        return true;
    });

    // Event handler to save dates filter.
    jqRoot.on(CustomEvents.events.activate, Selectors.filters.date.save, function() {
        // Populate the hidden form inputs to submit the data.
        let filtersForm = document.forms.filtersform;
        const datesPopover = root.querySelector(Selectors.filters.date.popover);
        const fromEnabled = datesPopover.querySelector('[name="filterdatefrompopover[enabled]"]').checked ? 1 : 0;
        const toEnabled = datesPopover.querySelector('[name="filterdatetopopover[enabled]"]').checked ? 1 : 0;

        // Disable the mform checker to prevent unsubmitted form warning to the user when closing the popover.
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });

        if (!fromEnabled && !toEnabled) {
            // Update the elements in the filter form.
            filtersForm.elements['datefrom[timestamp]'].value = 0;
            filtersForm.elements['datefrom[enabled]'].value = fromEnabled;
            filtersForm.elements['dateto[timestamp]'].value = 0;
            filtersForm.elements['dateto[enabled]'].value = toEnabled;

            // Submit the filter values and re-generate report.
            submitWithFilter('#filter-dates-popover');
        } else {
            let args = {data: []};

            if (fromEnabled) {
                args.data.push({
                    'key': 'from',
                    'year': datesPopover.querySelector('[name="filterdatefrompopover[year]"]').value,
                    'month': datesPopover.querySelector('[name="filterdatefrompopover[month]"]').value,
                    'day': datesPopover.querySelector('[name="filterdatefrompopover[day]"]').value,
                    'hour': 0,
                    'minute': 0
                });
            }

            if (toEnabled) {
                args.data.push({
                    'key': 'to',
                    'year': datesPopover.querySelector('[name="filterdatetopopover[year]"]').value,
                    'month': datesPopover.querySelector('[name="filterdatetopopover[month]"]').value,
                    'day': datesPopover.querySelector('[name="filterdatetopopover[day]"]').value,
                    'hour': 23,
                    'minute': 59
                });
            }

            const request = {
                methodname: 'core_calendar_get_timestamps',
                args: args
            };

            Ajax.call([request])[0].done(function(result) {
                let fromTimestamp = 0,
                    toTimestamp = 0;

                result['timestamps'].forEach(function(data){
                    if (data.key === 'from') {
                        fromTimestamp = data.timestamp;
                    } else if (data.key === 'to') {
                        toTimestamp = data.timestamp;
                    }
                });

                // Display an error if the from date is later than the do date.
                if (toTimestamp > 0 && fromTimestamp > toTimestamp) {
                    const warningdiv = document.getElementById('dates-filter-warning');
                    warningdiv.classList.remove('hidden');
                    warningdiv.classList.add('d-block');
                } else {
                    filtersForm.elements['datefrom[timestamp]'].value = fromTimestamp;
                    filtersForm.elements['datefrom[enabled]'].value = fromEnabled;
                    filtersForm.elements['dateto[timestamp]'].value = toTimestamp;
                    filtersForm.elements['dateto[enabled]'].value = toEnabled;

                    // Submit the filter values and re-generate report.
                    submitWithFilter('#filter-dates-popover');
                }
            });
        }
    });

    jqRoot.on(CustomEvents.events.activate, Selectors.filters.date.calendariconfrom, function() {
        updateCalendarPosition(Selectors.filters.date.calendariconfrom);
    });

    jqRoot.on(CustomEvents.events.activate, Selectors.filters.date.calendariconto, function() {
        updateCalendarPosition(Selectors.filters.date.calendariconto);
    });
};