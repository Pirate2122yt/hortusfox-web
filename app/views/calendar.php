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

    // The compiled bundle's renderCalendarMonth() (app.js) starts the
    // week on Monday and doesn't know about the auto-populated
    // water/fertilise/repot due-date chips (is_care_event/plant_id) or
    // task chips (is_task_event/task_id) - rather than requiring a
    // frontend rebuild for this, it's replaced wholesale here with the
    // corrected version (Sunday-first, and routing a care-event chip
    // click to the plant and a task chip click to the task instead of
    // the (nonexistent) calendar-item edit form). Once app.js is
    // rebuilt from the current app/resources/js/app.js source - which
    // already has this same corrected logic - this override is
    // redundant and can be deleted.
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.vue === 'undefined') {
            return;
        }

        window.vue.renderCalendarMonth = function() {
            if (typeof window.calendarViewYear === 'undefined') {
                let now = new Date();
                window.calendarViewYear = now.getFullYear();
                window.calendarViewMonth = now.getMonth();
            }

            let gridElem = document.getElementById('calendar-month-grid');
            let legendElem = document.getElementById('calendar-legend');
            let titleElem = document.getElementById('calendar-month-title');

            if (!gridElem) {
                return;
            }

            const year = window.calendarViewYear;
            const month = window.calendarViewMonth;
            const locale = document.documentElement.lang || 'en';

            const fmtDate = function(d) {
                return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            };

            const firstOfMonth = new Date(year, month, 1);
            const firstWeekday = firstOfMonth.getDay(); // Sunday = 0 .. Saturday = 6 - the week starts on Sunday
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const totalCells = Math.ceil((firstWeekday + daysInMonth) / 7) * 7;

            const gridStart = new Date(year, month, 1 - firstWeekday);
            const gridEnd = new Date(year, month, 1 - firstWeekday + totalCells);

            if (titleElem) {
                titleElem.textContent = firstOfMonth.toLocaleDateString(locale, { month: 'long', year: 'numeric' });
            }

            window.vue.ajaxRequest('post', window.location.origin + '/calendar/query', { date_from: fmtDate(gridStart), date_till: fmtDate(gridEnd) }, function(response) {
                if (response.code != 200) {
                    alert(response.msg);
                    return;
                }

                let data = response.data;

                data.sort(function(a, b) {
                    return new Date(a.date_from) - new Date(b.date_from);
                });

                let today = new Date();
                today.setHours(0, 0, 0, 0);
                const todayStr = fmtDate(today);

                if (legendElem) {
                    let seenClasses = {};
                    let legendFragment = document.createDocumentFragment();

                    data.forEach(function(item) {
                        if (!seenClasses[item.class_name]) {
                            seenClasses[item.class_name] = true;

                            let itemElem = document.createElement('span');
                            itemElem.className = 'calendar-legend-item';

                            let swatchElem = document.createElement('span');
                            swatchElem.className = 'calendar-legend-swatch';
                            swatchElem.style.backgroundColor = item.color_background;
                            swatchElem.style.borderColor = item.color_border;

                            itemElem.appendChild(swatchElem);
                            itemElem.appendChild(document.createTextNode(item.class_name));
                            legendFragment.appendChild(itemElem);
                        }
                    });

                    legendElem.innerHTML = '';
                    legendElem.appendChild(legendFragment);
                }

                const weekdayFormatter = new Intl.DateTimeFormat(locale, { weekday: 'short' });
                let headerFragment = document.createDocumentFragment();

                for (let i = 0; i < 7; i++) {
                    let d = new Date(gridStart);
                    d.setDate(gridStart.getDate() + i);

                    let headerCell = document.createElement('div');
                    headerCell.className = 'calendar-weekday-header' + (((i === 0) || (i === 6)) ? ' is-weekend' : '');
                    headerCell.textContent = weekdayFormatter.format(d);
                    headerFragment.appendChild(headerCell);
                }

                const addHint = gridElem.dataset.addHint || '';
                let dayFragment = document.createDocumentFragment();

                for (let i = 0; i < totalCells; i++) {
                    let cellDate = new Date(gridStart);
                    cellDate.setDate(gridStart.getDate() + i);
                    const cellDateStr = fmtDate(cellDate);

                    let cellElem = document.createElement('div');
                    cellElem.className = 'calendar-day-cell';

                    if (cellDate.getMonth() !== month) {
                        cellElem.classList.add('is-outside-month');
                    }

                    const dow = cellDate.getDay();
                    if (dow === 0 || dow === 6) {
                        cellElem.classList.add('is-weekend');
                    }

                    if (cellDateStr === todayStr) {
                        cellElem.classList.add('is-today');
                    }

                    let numberElem = document.createElement('div');
                    numberElem.className = 'calendar-day-number';
                    numberElem.textContent = cellDate.getDate();
                    cellElem.appendChild(numberElem);

                    let eventsElem = document.createElement('div');
                    eventsElem.className = 'calendar-day-events';

                    data.forEach(function(item) {
                        const itemFrom = item.date_from.split(' ')[0];
                        const itemTill = item.date_till.split(' ')[0];

                        if ((cellDateStr >= itemFrom) && (cellDateStr <= itemTill)) {
                            let chip = document.createElement('div');
                            chip.className = 'calendar-event-chip' + (item.is_care_event ? ' is-care-event' : '') + (item.is_task_event ? ' is-task-event' : '');
                            chip.style.backgroundColor = item.color_background;
                            chip.style.borderColor = item.color_border;
                            chip.title = item.name + ' (' + item.class_name + ')';
                            chip.textContent = item.name;

                            chip.addEventListener('click', function(ev) {
                                ev.stopPropagation();

                                // Auto-populated care due-dates and tasks
                                // aren't real calendar rows - there's
                                // nothing to edit here, so go to the
                                // plant/task instead.
                                if (item.is_care_event) {
                                    window.location.href = window.location.origin + '/plants/details/' + item.plant_id;
                                } else if (item.is_task_event) {
                                    window.location.href = window.location.origin + '/tasks#task-anchor-' + item.task_id;
                                } else {
                                    window.vue.editCalendarItemFromData(item);
                                }
                            });

                            eventsElem.appendChild(chip);
                        }
                    });

                    cellElem.appendChild(eventsElem);
                    cellElem.title = addHint;

                    cellElem.addEventListener('click', function() {
                        window.vue.openAddCalendarItem(cellDateStr);
                    });

                    dayFragment.appendChild(cellElem);
                }

                gridElem.innerHTML = '';

                let headerRow = document.createElement('div');
                headerRow.className = 'calendar-weekday-header-row';
                headerRow.appendChild(headerFragment);
                gridElem.appendChild(headerRow);

                let daysGrid = document.createElement('div');
                daysGrid.className = 'calendar-days-grid';
                daysGrid.appendChild(dayFragment);
                gridElem.appendChild(daysGrid);
            });
        };

        // The grid may already have rendered once via the stale bundle's
        // version before this ran - render again with the corrected one.
        window.vue.renderCalendarMonth();
    });
</script>
