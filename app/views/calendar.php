<h1>{{ __('app.calendar') }}</h1>

<h2 class="smaller-headline">{{ __('app.calendar_hint') }}</h2>

@include('flashmsg.php')

<div class="calendar-add">
    <a class="button is-info" href="javascript:void(0);" onclick="window.vue.openAddCalendarItem();">{{ __('app.add') }}</a>
</div>

<div class="calendar-toolbar">
    <div class="calendar-toolbar-nav">
        <button type="button" class="button calendar-nav-btn" onclick="window.vue.shiftCalendarMonth(-1);" aria-label="{{ __('app.calendar_prev_month') }}"><i class="fas fa-chevron-left"></i></button>
        <h2 class="calendar-month-title" id="calendar-month-title">&nbsp;</h2>
        <button type="button" class="button calendar-nav-btn" onclick="window.vue.shiftCalendarMonth(1);" aria-label="{{ __('app.calendar_next_month') }}"><i class="fas fa-chevron-right"></i></button>
    </div>
    <button type="button" class="button is-link" onclick="window.vue.goToCalendarToday();">{{ __('app.calendar_today') }}</button>
</div>

<div class="calendar-legend" id="calendar-legend"></div>

<div class="calendar-month-grid" id="calendar-month-grid" data-add-hint="{{ __('app.calendar_add_for_day') }}"></div>

<script>
    // The Location field is new and the compiled bundle's
    // editCalendarItemFromData() (app.js) doesn't know to populate it, so
    // wrap it here rather than requiring a frontend rebuild for this. If
    // the bundle's markup/behavior ever changes shape, the extra field
    // just doesn't get pre-filled - it never breaks opening the edit form.
    document.addEventListener('DOMContentLoaded', function() {
        if ((typeof window.vue === 'undefined') || (typeof window.vue.editCalendarItemFromData !== 'function')) {
            return;
        }

        let originalEditCalendarItemFromData = window.vue.editCalendarItemFromData;

        window.vue.editCalendarItemFromData = function(item) {
            originalEditCalendarItemFromData(item);

            let locationSelect = document.getElementById('inpEditCalendarItemLocation');
            if (locationSelect) {
                locationSelect.value = ((item.location !== undefined) && (item.location !== null)) ? item.location : '';
            }
        };
    });
</script>
