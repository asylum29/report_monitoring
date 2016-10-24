define(['jquery'], function ($) {
    return {
        init: function () {
            $(document).ready(function () {
                var more = function () {
                    $(this).parents('tr').next().children().toggle();
                    $(this).toggleClass('report_monitoring_showmore report_monitoring_hidemore');
                }
                $('td.report_monitoring_coursestats').hide();
                $('.report_monitoring_showmore').on('click', more);
            });
        }
    }
});
