<div class="columns">
    <div class="column is-1"></div>

    <div class="column is-10">
        <h1>{{ __('app.chat') }}</h1>

        <h2 class="smaller-headline">{{ __('app.chat_hint') }}</h2>

        @include('flashmsg.php')

        <div class="margin-vertical">
            <form id="frmSendChatMessage" method="POST" action="{{ url('/chat/add') }}">
                @csrf

                <div class="field has-addons">
                    <div class="control is-stretched">
                        <textarea class="textarea is-input-dark" name="message" onkeypress="window.vue.handleChatInput();"></textarea>
                    </div>
                </div>

                <div class="control">
                    <a class="button is-success" href="javascript:void(0);" onclick="document.getElementById('frmSendChatMessage').submit();">{{ __('app.send') }}</a>
                </div>
            </form>
        </div>

        @if (app('chat_showusers', false))
            <div class="chat-user-list" id="chat-user-list"></div>
        @endif

        @if (app('chat_system'))
            <label class="checkbox plant-journal-system-toggle chat-system-toggle">
                <input type="checkbox" id="chat-toggle-system" onchange="window.vue.toggleChatSystemMessages(this.checked);">
                {{ __('app.chat_show_system_label') }}
            </label>
        @endif

        <div class="chat" id="chat">
            <div class="chat-message chat-typing-indicator">
                <div class="chat-message-content">
                    <span><i id="chat-typing-circle-1" class="fas fa-circle"></i></span>
                    <span><i id="chat-typing-circle-2" class="fas fa-circle"></i></span>
                    <span><i id="chat-typing-circle-3" class="fas fa-circle"></i></span>
                </div>
            </div>

            @if (isset($messages))
                @foreach ($messages as $message)
                    @if (!$message->get('sysmsg'))
                        <div class="chat-message {{ ($message->get('userId') == $user->get('id')) ? 'chat-message-right' : '' }}">
                            <div class="chat-message-user">
                                <div class="is-inline-block" style="color: {{ UserModel::getChatColorForUser($message->get('userId')) }};">{{ UserModel::getNameById($message->get('userId')) }}</div>
                                @if (ChatViewModel::handleNewMessage($user->get('id'), $message->get('id')))
                                    <div class="chat-message-new">{{ __('app.new') }}</div>
                                @endif
                            </div>

                            <div class="chat-message-content">
                                <pre>{!! UtilsModule::purify(UtilsModule::translateURLs($message->get('message'))) !!}</pre>
                            </div>

                            <div class="chat-message-info">
                                {{ (new Carbon($message->get('created_at')))->diffForHumans() }}

                                @if ((int)$message->get('userId') === (int)$user->get('id'))
                                    &nbsp;<a href="javascript:void(0);" title="{{ __('app.edit') }}" onclick="chatEditMessage({{ $message->get('id') }}, this);"><i class="fas fa-pen"></i></a>
                                @endif

                                @if (UserModel::isCurrentlyAdmin())
                                    &nbsp;<a href="javascript:void(0);" title="{{ __('app.remove') }}" onclick="if (confirm(CHAT_DELETE_CONFIRM)) { chatDeleteMessage({{ $message->get('id') }}, this); }"><i class="fas fa-trash-alt"></i></a>
                                @endif
                            </div>
                        </div>
                    @else
                        <?php $isNewMessage = ChatViewModel::handleNewMessage($user->get('id'), $message->get('id')); ?>

                        <div class="system-message">
                            <div class="system-message-left {{ ($isNewMessage) ? 'system-message-left-new' : '' }}">
                                <div class="system-message-context" title="{{ date('Y-m-d H:i:s', strtotime($message->get('created_at'))) }}">{{ ($message->get('display_name') ?: (($message->get('userId')) ? UserModel::getNameById($message->get('userId')) : 'System')) . ' @ ' . (new Carbon(strtotime($message->get('created_at'))))->diffForHumans() }}</div>

                                <div class="system-message-content">{!! UtilsModule::purify($message->get('message')) !!}</div>
                            </div>

                            @if (($isNewMessage) || (UserModel::isCurrentlyAdmin()))
                                <div class="system-message-right">
                                    @if ($isNewMessage)
                                        <div class="system-message-new chat-message-new">{{ __('app.new') }}</div>
                                    @endif

                                    @if (UserModel::isCurrentlyAdmin())
                                        <a href="javascript:void(0);" title="{{ __('app.remove') }}" onclick="if (confirm(CHAT_DELETE_CONFIRM)) { chatDeleteMessage({{ $message->get('id') }}, this); }"><i class="fas fa-trash-alt"></i></a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>

    <div class="column is-1"></div>
</div>

<script>
    const CHAT_DELETE_CONFIRM = {!! json_encode(__('app.confirm_remove_chat_message')) !!};
    const CHAT_EDIT_SAVE_LABEL = {!! json_encode(__('app.save')) !!};
    const CHAT_EDIT_CANCEL_LABEL = {!! json_encode(__('app.cancel')) !!};

    function chatDeleteMessage(id, el) {
        window.vue.ajaxRequest('post', window.location.origin + '/chat/message/remove', { message: id }, function(response) {
            if (response.code == 200) {
                let container = el.closest('.chat-message') || el.closest('.system-message');
                if (container) {
                    container.remove();
                }
            } else {
                alert(response.msg);
            }
        });
    }

    function chatEditMessage(id, el) {
        let container = el.closest('.chat-message');
        if (!container) {
            return;
        }

        let contentEl = container.querySelector('.chat-message-content');
        let pre = contentEl ? contentEl.querySelector('pre') : null;
        if ((!contentEl) || (!pre)) {
            return;
        }

        let originalHtml = contentEl.innerHTML;
        let originalText = pre.textContent;

        contentEl.innerHTML = '';

        let textarea = document.createElement('textarea');
        textarea.className = 'textarea is-input-dark';
        textarea.value = originalText;
        contentEl.appendChild(textarea);

        let actions = document.createElement('div');
        actions.style.marginTop = '0.5rem';
        actions.style.display = 'flex';
        actions.style.gap = '0.5rem';

        let saveBtn = document.createElement('a');
        saveBtn.className = 'button is-success is-small';
        saveBtn.href = 'javascript:void(0);';
        saveBtn.textContent = CHAT_EDIT_SAVE_LABEL;
        saveBtn.onclick = function() {
            window.vue.ajaxRequest('post', window.location.origin + '/chat/message/edit', { message: id, newMessage: textarea.value }, function(response) {
                if (response.code == 200) {
                    contentEl.innerHTML = '<pre>' + response.message + '</pre>';
                } else {
                    alert(response.msg);
                    contentEl.innerHTML = originalHtml;
                }
            });
        };

        let cancelBtn = document.createElement('a');
        cancelBtn.className = 'button is-small';
        cancelBtn.href = 'javascript:void(0);';
        cancelBtn.textContent = CHAT_EDIT_CANCEL_LABEL;
        cancelBtn.onclick = function() {
            contentEl.innerHTML = originalHtml;
        };

        actions.appendChild(saveBtn);
        actions.appendChild(cancelBtn);
        contentEl.appendChild(actions);

        textarea.focus();
    }

    // Messages that arrive live via the periodic /chat/query poll are
    // rendered by window.vue.renderNewChatMessage(), which lives in the
    // compiled app.js bundle and has no edit/delete affordance built in.
    // Rather than requiring a frontend rebuild to add one, wrap the
    // existing function here and splice the right icons into the HTML it
    // returns - an edit icon when the live message belongs to the current
    // user, a delete icon when the current user is an admin - so
    // live-polled messages get the same options as the ones rendered
    // server-side above. If the bundle's markup for this ever changes
    // shape, the string replace below just silently doesn't match - it
    // never breaks the underlying chat feature.
    document.addEventListener('DOMContentLoaded', function() {
        if ((typeof window.vue === 'undefined') || (typeof window.vue.renderNewChatMessage !== 'function')) {
            return;
        }

        let originalRenderNewChatMessage = window.vue.renderNewChatMessage;
        let isAdmin = {!! json_encode(UserModel::isCurrentlyAdmin()) !!};
        let authUserId = {{ (int)$user->get('id') }};

        window.vue.renderNewChatMessage = function(elem, auth_user) {
            let html = originalRenderNewChatMessage(elem, auth_user);
            let actionLinks = '';

            if ((elem.userId) && (parseInt(elem.userId) === authUserId)) {
                actionLinks += '<a href="javascript:void(0);" onclick="chatEditMessage(' + elem.id + ', this);"><i class="fas fa-pen"></i></a>';
            }

            if (isAdmin) {
                actionLinks += '<a href="javascript:void(0);" onclick="if (confirm(CHAT_DELETE_CONFIRM)) { chatDeleteMessage(' + elem.id + ', this); }"><i class="fas fa-trash-alt"></i></a>';
            }

            if ((actionLinks) && (/<\/div>\s*$/.test(html))) {
                html = html.replace(/<\/div>(\s*)$/, actionLinks + '</div>$1');
            }

            return html;
        };
    });
</script>