<h1>{{ __('app.tasks') }}</h1>

<h2 class="smaller-headline">{{ __('app.tasks_hint') }}</h2>

@include('flashmsg.php')

<div class="margin-vertical">
    <a class="button is-success" href="javascript:void(0);" onclick="window.vue.bShowCreateTask = true;">{{ __('app.create_new') }}</a>
</div>

<div class="margin-vertical">
    <a class="is-default-link {{ ((!isset($_GET['done'])) || ($_GET['done'] == false)) ? 'is-underlined' : '' }}" href="{{ url('/tasks') }}">{{ __('app.tasks_todo') }}</a>&nbsp;|&nbsp;<a class="is-default-link {{ ((isset($_GET['done'])) && ($_GET['done'] == true)) ? 'is-underlined' : '' }}" href="{{ url('/tasks?done=1') }}">{{ __('app.tasks_done') }}</a>
</div>

<div class="sorting-control is-rounded is-small is-margin-bottom-20">
    <input type="text" id="tasks-filter" placeholder="{{ __('app.filter_by_text') }}">
</div>

@if (isset($tasks))
    <div class="tasks">
        @if (count($tasks) > 0)
            @foreach ($tasks as $task)
                <?php
                    // Built as a plain PHP string, not a template
                    // conditional directive - those must start their own
                    // line in this template engine (mixing one into the
                    // title line below silently broke it). Also kept
                    // OUTSIDE the #task-item-title-X element below, since
                    // its exact text content is scraped by editTask() in
                    // app.js and nothing extra should join it.
                    $task_category_item = CalendarClassModel::findClass($task->get('category'));
                    $task_category_badge = '';
                    if ($task_category_item) {
                        $task_category_badge = '<span class="task-category-badge" style="background-color: ' . htmlspecialchars($task_category_item->get('color_background'), ENT_QUOTES) . '; border-color: ' . htmlspecialchars($task_category_item->get('color_border'), ENT_QUOTES) . ';">' . htmlspecialchars(__($task_category_item->get('name'))) . '</span>';
                    }
                ?>
                <div class="task" id="task-item-{{ $task->get('id') }}" data-category="{{ $task->get('category') }}">
                    <a name="task-anchor-{{ $task->get('id') }}"></a>

                    <div class="task-header">
                        <div class="task-header-title" id="task-item-title-{{ $task->get('id') }}"><span>#{{ sprintf('%03d', $task->get('id')) }}</span> {{ $task->get('title') }}</div>
                        {!! $task_category_badge !!}
                        <div class="task-header-action">
                            <span><a href="javascript:void(0);" onclick="window.vue.editTask({{ $task->get('id') }});"><i class="fas fa-edit"></i></a></span>
                            <span><a href="javascript:void(0);" onclick="if (confirm('{{ __('app.confirm_remove_task') }}')) { window.vue.removeTask({{ $task->get('id') }}); }"><i class="fas fa-trash-alt"></i></a></span>
                        </div>
                    </div>

                    <?php
                        $plant_link = '';
                        if (PlantTasksRefModel::hasPlantReference($task->get('id'))) {
                            $plant_data = PlantsModel::getDetails(PlantTasksRefModel::getForTask($task->get('id'))?->get('plant_id'));
                            if ($plant_data) {
                                $plant_link = html_entity_decode('&#x1fab4', ENT_COMPAT | ENT_QUOTES) . '&nbsp;<a href="' . url('/plants/details/' . $plant_data->get('id')) . '">' . $plant_data->get('name') . '</a><br/><br/>';
                            }
                        }
                    ?>

                    <div class="task-description" id="task-item-description-{{ $task->get('id') }}"><pre>{!! $plant_link . (($task->get('description')) ? UtilsModule::purify(UtilsModule::translateURLs($task->get('description'))) : 'N/A') !!}</pre></div>

                    <div class="task-footer">
                        <div class="task-footer-date">{{ (new Carbon($task->get('created_at')))->diffForHumans() }}</div>

                        <div class="task-footer-due" id="task-item-due-{{ $task->get('id') }}">
                            @if ($task->get('due_date') !== null)
                                <span class="{{ ((new DateTime($task->get('due_date'))) < (new DateTime())) ? 'is-task-overdue' : '' }}">{{ date('Y-m-d', strtotime($task->get('due_date'))) }}</span>
                                
                                @if ($task->get('recurring_time') !== null)
                                    <span class="is-task-recurring" data-time="{{ TasksModel::translateScope($task->get('recurring_time'), $task->get('recurring_scope')) ?? '' }}" data-scope="{{ $task->get('recurring_scope') ?? '' }}"><i class="far fa-clock {{ ((!$task->get('recurring_time')) ? 'is-hidden' : '') }}"></i></span>
                                @endif
                            @endif
                        </div>
                        
                        <div class="task-footer-action">
                            <input type="radio" onclick="window.vue.toggleTaskStatus({{ $task->get('id') }});" {{ ($task->get('done')) ? 'checked' : '' }} /><a href="javascript:void(0);" onclick="window.vue.toggleTaskStatus({{ $task->get('id') }});">&nbsp;{{ __('app.done') }}</a>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            @if ((!isset($_GET['done'])) || ($_GET['done'] == 0))
            <div class="tasks-all-done">
                <div class="tasks-all-done-image">
                    <img src="{{ asset('img/partypopper.png') }}" alt="image"/>
                </div>

                <div class="tasks-all-done-text">{{ __('app.all_tasks_done') }}</div>
            </div>
            @endif
        @endif
    </div>
@endif

<script>
    // The Category field is new and the compiled bundle's editTask()
    // (app.js) doesn't know to populate it, so wrap it here rather than
    // requiring a frontend rebuild for this. If the bundle's editTask()
    // ever changes shape, the category select just doesn't get
    // pre-filled - it never breaks opening the edit form. Once app.js
    // is rebuilt from the current app/resources/js/app.js source -
    // which already populates this field - this override is redundant
    // and can be deleted.
    document.addEventListener('DOMContentLoaded', function() {
        if ((typeof window.vue === 'undefined') || (typeof window.vue.editTask !== 'function')) {
            return;
        }

        let originalEditTask = window.vue.editTask;

        window.vue.editTask = function(id) {
            originalEditTask(id);

            let taskElem = document.getElementById('task-item-' + id);
            let categorySelect = document.getElementById('inpEditTaskCategory');
            if ((taskElem) && (categorySelect)) {
                categorySelect.value = taskElem.dataset.category || 'other';
            }
        };
    });
</script>
