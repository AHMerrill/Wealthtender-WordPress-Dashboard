/**
 * Wealthtender Analytics - Team Comparisons Page
 * Handles partner group team comparisons (spider + grouped bar charts)
 */

(function($) {
    'use strict';

    // State management
    const state = {
        teamGroupCode: null,
        teamMethod: 'mean',
        partnerGroups: null,
    };

    /**
     * Initialize the team comparisons page
     */
    function init() {
        loadPartnerGroups();
        setupEventListeners();
    }

    /**
     * Load partner groups from API
     */
    function loadPartnerGroups() {
        WT.api('comparisons/partner-groups')
            .then(function(groups) {
                state.partnerGroups = groups;
                populatePartnerGroupDropdown();
            })
            .catch(function(error) {
                console.error('Failed to load partner groups:', error);
                WT.emptyState('#team-comp-spider', 'Failed to load partner groups');
            });
    }

    /**
     * Populate partner group dropdown
     */
    function populatePartnerGroupDropdown() {
        if (!state.partnerGroups) return;

        const options = state.partnerGroups.map(function(group) {
            return '<option value="' + group.group_code + '">' + group.group_name + '</option>';
        }).join('');

        $('#team-group-select').html('<option value="">Select Partner Group</option>' + options);
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Method change
        $('#team-method-select').on('change', function() {
            state.teamMethod = $(this).val();
            if (state.teamGroupCode) {
                fetchTeamComparison();
            }
        });

        // Partner group selection
        $('#team-group-select').on('change', function() {
            state.teamGroupCode = $(this).val() || null;
            if (state.teamGroupCode) {
                fetchTeamComparison();
            } else {
                clearTeamComparison();
            }
        });
    }

    /**
     * Clear team comparison displays
     */
    function clearTeamComparison() {
        $('#team-comp-spider').empty();
        $('#team-comp-bars').empty();
    }

    /**
     * Fetch team comparison data
     */
    function fetchTeamComparison() {
        WT.showLoading('#team-comp-spider');
        WT.showLoading('#team-comp-bars');

        var params = {
            method: state.teamMethod,
        };

        WT.api('comparisons/partner-group/' + state.teamGroupCode, params)
            .then(function(data) {
                renderTeamComparison(data);
            })
            .catch(function(error) {
                console.error('Failed to fetch team comparison:', error);
                WT.emptyState('#team-comp-spider', 'Failed to load team data');
                WT.emptyState('#team-comp-bars', 'Failed to load team data');
            });
    }

    /**
     * Render team comparison (spider chart and bar chart)
     */
    function renderTeamComparison(data) {
        if (!data.members || data.members.length === 0) {
            WT.emptyState('#team-comp-spider', 'No members found');
            WT.emptyState('#team-comp-bars', 'No members found');
            return;
        }

        renderTeamSpider(data);
        renderTeamBars(data);
    }

    /**
     * Render team spider chart
     */
    function renderTeamSpider(data) {
        var traces = [];

        data.members.forEach(function(member, i) {
            if (member.enriched) {
                var dimValues = {};
                wtAnalytics.dimensions.forEach(function(d) {
                    dimValues[d] = member.enriched[d] ? (member.enriched[d].percentile || 0) : 0;
                });
                var trace = WT.buildSpiderTrace(dimValues, member.entity_name, wtAnalytics.dataVizPalette[i % 10]);
                traces.push(trace);
            }
        });

        WT.plot('team-comp-spider', traces, WT.spiderLayout(
            data.group_name + ' — Team Comparison',
            100
        ));
    }

    /**
     * Render team bar chart
     */
    function renderTeamBars(data) {
        var traces = data.members.map(function(member, i) {
            return {
                type: 'bar',
                name: member.entity_name,
                x: wtAnalytics.dimensions.map(function(d) { return wtAnalytics.dimShort[d]; }),
                y: wtAnalytics.dimensions.map(function(d) {
                    return member.enriched && member.enriched[d] ? (member.enriched[d].percentile || 0) : 0;
                }),
                marker: { color: wtAnalytics.dataVizPalette[i % 10] },
                hovertemplate: '%{x}: %{y:.1f}<extra>' + member.entity_name + '</extra>',
            };
        });

        WT.plot('team-comp-bars', traces, WT.baseLayout({
            barmode: 'group',
            title: { text: 'Team Scores by Dimension' },
            yaxis: { title: 'Percentile', range: [0, 100] },
            height: 400,
        }));
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        init();
    });

})(jQuery);
