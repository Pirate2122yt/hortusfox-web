/**
 * app.js
 * 
 * Put here your application specific JavaScript implementations
 */

import './../sass/app.scss';

window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import '@fortawesome/fontawesome-free/scss/fontawesome.scss';
import '@fortawesome/fontawesome-free/js/all.min.js';
import '@fortawesome/fontawesome-free/js/solid.js';
import '@fortawesome/fontawesome-free/js/brands.js';

import Chart from 'chart.js/auto';
import 'chartjs-adapter-date-fns';

/**
 * Chart.js plugin: shades weekend (Saturday/Sunday) columns on the
 * calendar's day-based time scale so it is easier to tell which
 * bars fall on a weekend at a glance.
 */
const calendarWeekendShadingPlugin = {
    id: 'calendarWeekendShading',
    beforeDatasetsDraw(chart) {
        const xScale = chart.scales.x;
        const yScale = chart.scales.y;

        if (!xScale || !yScale) {
            return;
        }

        const ctx = chart.ctx;
        ctx.save();
        ctx.fillStyle = 'rgba(255, 255, 255, 0.05)';

        let cursor = new Date(xScale.min);
        cursor.setHours(0, 0, 0, 0);
        const end = new Date(xScale.max);

        for (let i = 0; (cursor <= end) && (i < 400); i++) {
            const day = cursor.getDay();

            if (day === 0 || day === 6) {
                const dayEnd = new Date(cursor);
                dayEnd.setDate(dayEnd.getDate() + 1);

                const xStart = xScale.getPixelForValue(cursor.getTime());
                const xEnd = xScale.getPixelForValue(dayEnd.getTime());

                ctx.fillRect(xStart, yScale.top, xEnd - xStart, yScale.bottom - yScale.top);
            }

            cursor.setDate(cursor.getDate() + 1);
        }

        ctx.restore();
    }
};

/**
 * Chart.js plugin: draws a vertical line marking the current date on
 * the calendar's day-based time scale.
 */
const calendarTodayLinePlugin = {
    id: 'calendarTodayLine',
    afterDatasetsDraw(chart) {
        const xScale = chart.scales.x;
        const yScale = chart.scales.y;

        if (!xScale || !yScale) {
            return;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const todayX = xScale.getPixelForValue(today.getTime());

        if ((todayX < xScale.left) || (todayX > xScale.right)) {
            return;
        }

        const ctx = chart.ctx;
        ctx.save();
        ctx.strokeStyle = 'rgb(230, 90, 90)';
        ctx.lineWidth = 2;
        ctx.setLineDash([5, 4]);
        ctx.beginPath();
        ctx.moveTo(todayX, yScale.top);
        ctx.lineTo(todayX, yScale.bottom);
        ctx.stroke();
        ctx.restore();
    }
};

window.constChatMessageQueryRefreshRate = 1000 * 15;
window.constChatUserListRefreshRate = 1000 * 15;
window.constChatTypingRefreshRate = 2000;

window.vue = null;

window.createVueInstance = function(element) {
    return new Vue({
        el: element,

        data: {
            bShowAddPlant: false,
            bShowEditText: false,
            bShowEditMultilineText: false,
            bShowEditBoolean: false,
            bShowEditInteger: false,
            bShowEditDate: false,
            bShowEditCombo: false,
            bShowEditPhoto: false,
            bShowEPUrl: false,
            bShowEditLinkText: false,
            bShowUploadPhoto: false,
            bShowSetPhotoURL: false,
            bShowCreateTask: false,
            bShowEditTask: false,
            bShowEditPreferences: false,
            bShowAddInventoryItem: false,
            bShowEditInventoryItem: false,
            bShowInvItemQRCode: false,
            bShowInventoryBulkPrint: false,
            bShowInventoryExport: false,
            bShowManageGroups: false,
            bInvGroupChanges: false,
            bShowRestorePassword: false,
            bShowCreateNewUser: false,
            bShowCreateNewLocation: false,
            bShowRemoveLocation: false,
            bShowPreviewImageModal: false,
            bShowSharePhoto: false,
            bShowAddFirstLocation: false,
            bShowAddCalendarItem: false,
            bShowEditCalendarItem: false,
            bShowCreateNewCalendarClass: false,
            bShowPlantQRCode: false,
            bShowPlantBulkPerformUpdate: false,
            bShowPlantBulkPrint: false,
            bShowAddCustomPlantAttribute: false,
            bShowEditCustomPlantAttribute: false,
            bShowCreateNewAttributeSchema: false,
            bShowAddPlantAttachment: false,
            bShowEditPlantAttachment: false,
            bShowAddPlantLogEntry: false,
            bShowEditPlantLogEntry: false,
            bShowAddLocationLogEntry: false,
            bShowEditLocationLogEntry: false,
            bShowCreateNewBulkCmd: false,
            bShowSelectRecognizedPlant: false,
            bShowQuickScanPlant: false,
            clsLastImagePreviewAspect: '',
            comboLocation: [],
            comboCuttingMonth: [],
            comboLifespan: [],
            comboLightLevel: [],
            comboHealthState: [],
            loading_please_wait: 'Please wait...',
            confirmPhotoRemoval: 'Are you sure you want to remove this photo?',
            confirmPlantRemoval: 'Are you sure you want to remove this plant?',
            confirmSetAllWatered: 'Are you sure you want to update the last watered date of all these plants?',
            confirmSetAllRepotted: 'Are you sure you want to update the last repotted date of all these plants?',
            confirmSetAllFertilised: 'Are you sure you want to update the last fertilised date of all these plants?',
            confirmInventoryItemRemoval: 'Are you sure you want to remove this item?',
            confirmPlantAddHistory: 'Please confirm if you want to do this action.',
            confirmPlantRemoveHistory: 'Please confirm if you want to do this action.',
            confirmRemovePlantAttachment: 'Do you really want to remove this attachment?',
            confirmRemovePlantLogEntry: 'Do you really want to remove this entry?',
            plantJournalSystemBadge: 'System',
            confirmRemoveLocationLogEntry: 'Do you really want to remove this entry?',
            confirmRemoveSharedPlantPhoto: 'Do you really want to remove this item?',
            confirmSetGalleryPhotoAsMain: 'Do you want to replace the main photo with this one?',
            addItem: 'Add',
            newChatMessage: 'New',
            currentlyOnline: 'Currently online: ',
            loadingPleaseWait: 'Please wait...',
            noListItemsSelected: 'No items selected',
            editProperty: 'Edit property',
            loadMore: 'Load more',
            operationSucceeded: 'Operation succeeded',
            copiedToClipboard: 'Content has been copied to clipboard.',
            origTestMailButtonContent: '',
            chatTypingEnable: false,
            chatTypingTimer: null,
            chatTypingHide: null,
            chatTypingCounter: 1
        },

        methods: {
            ajaxRequest: function (method, url, data = {}, successfunc = function(data){}, finalfunc = function(){}, config = {})
            {
                let func = window.axios.get;
                if (method == 'post') {
                    func = window.axios.post;
                } else if (method == 'patch') {
                    func = window.axios.patch;
                } else if (method == 'delete') {
                    func = window.axios.delete;
                }

                func(url, data, config)
                    .then(function(response){
                        successfunc(response.data);
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .finally(function(){
                            finalfunc();
                        }
                    );
            },

            initNavBar: function()
            {
                const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

                if ($navbarBurgers.length > 0) {
                    $navbarBurgers.forEach( el => {
                        el.addEventListener('click', () => {
                            const target = el.dataset.target;
                            const $target = document.getElementById(target);

                            el.classList.toggle('is-active');
                            $target.classList.toggle('is-active');
                        });
                    });
                }
            },

            getCookieValue: function (name, def = null)
            {
                let cookies = document.cookie.split(';');

                for (let i = 0; i < cookies.length; i++) {
                    if (cookies[i].indexOf(name) !== -1) {
                        return cookies[i].substr(cookies[i].indexOf('=') + 1);
                    }
                }

                return def;
            },

            setCookieValue: function(name, value)
            {
                let expDate = new Date(Date.now() + 1000 * 60 * 60 * 24 * 365);
                document.cookie = name + '=' + value + '; path=/; expires=' + expDate.toUTCString() + ';';
            },

            showEditText: function(plant, property, defval, anchor = '')
            {
                document.getElementById('inpEditTextPlantId').value = plant;
                document.getElementById('inpEditTextAttribute').value = property;
                document.getElementById('inpEditTextValue').value = defval;
                document.getElementById('inpEditTextAnchor').value = anchor;
                window.vue.bShowEditText = true;
            },

            showEditMultilineText: function(plant, property, defval, anchor = '')
            {
                document.getElementById('inpEditMultilineTextPlantId').value = plant;
                document.getElementById('inpEditMultilineTextAttribute').value = property;
                document.getElementById('inpEditMultilineTextValue').value = defval;
                document.getElementById('inpEditMultilineTextAnchor').value = anchor;
                window.vue.bShowEditMultilineText = true;
            },

            showEditBoolean: function(plant, property, hint, defval)
            {
                document.getElementById('inpEditBooleanPlantId').value = plant;
                document.getElementById('inpEditBooleanAttribute').value = property;
                document.getElementById('property-hint').innerHTML = hint;

                if (defval) {
                    document.getElementById('inpEditBooleanValue_yes').checked = true;
                } else {
                    document.getElementById('inpEditBooleanValue_no').checked = true;
                }
                
                window.vue.bShowEditBoolean = true;
            },

            showEditInteger: function(plant, property, defval)
            {
                document.getElementById('inpEditIntegerPlantId').value = plant;
                document.getElementById('inpEditIntegerAttribute').value = property;
                document.getElementById('inpEditIntegerValue').value = defval;
                window.vue.bShowEditInteger = true;
            },

            showEditDate: function(plant, property, defval)
            {
                document.getElementById('inpEditDatePlantId').value = plant;
                document.getElementById('inpEditDateAttribute').value = property;
                document.getElementById('inpEditDateValue').value = defval;
                window.vue.bShowEditDate = true;
            },

            showEditCombo: function(plant, property, combo, defval)
            {
                document.getElementById('inpEditComboPlantId').value = plant;
                document.getElementById('inpEditComboAttribute').value = property;
                
                if (typeof combo !== 'object') {
                    console.error('Invalid combo specified');
                    return;
                }

                let sel = document.getElementById('selEditCombo');
                if (sel) {
                    for (let i = sel.options.length - 1; i >= 0; i--) {
                        sel.remove(i);
                    }

                    combo.forEach(function(elem, index){
                        let opt = document.createElement('option');
                        opt.value = elem.ident;
                        opt.text = elem.label;
                        sel.add(opt);
                    });
                }

                document.getElementById('selEditCombo').value = defval;

                window.vue.bShowEditCombo = true;
            },

            showEditLinkText: function(plant, text, link)
            {
                document.getElementById('inpEditLinkTextPlantId').value = plant;
                document.getElementById('inpEditLinkTextValue').value = text;
                document.getElementById('inpEditLinkTextLink').value = link;
                window.vue.bShowEditLinkText = true;
            },

            selectDataTypeInputField: function(elem, field) {
                if (elem.selectedIndex <= 0) {
                    return;
                }
                
                field.classList.remove('is-hidden');

                if (!field.children[1].children[0].classList.contains('is-hidden')) {
                    field.children[1].children[0].classList.add('is-hidden');
                }

                if (!field.children[1].children[1].classList.contains('is-hidden')) {
                    field.children[1].children[1].classList.add('is-hidden');
                }

                if (!field.children[1].children[2].classList.contains('is-hidden')) {
                    field.children[1].children[2].classList.add('is-hidden');
                }

                field.children[1].children[0].children[0].children[1].children[0].children[0].disabled = true;
                field.children[1].children[0].children[0].children[2].children[0].children[0].disabled = true;
                field.children[1].children[1].disabled = true;
                field.children[1].children[2].disabled = true;

                if (elem.value === 'bool') {
                    field.children[1].children[0].classList.remove('is-hidden');
                    field.children[1].children[0].children[0].children[1].children[0].children[0].disabled = false;
                    field.children[1].children[0].children[0].children[2].children[0].children[0].disabled = false;
                } else if (elem.value === 'datetime') {
                    field.children[1].children[2].classList.remove('is-hidden');
                    field.children[1].children[2].disabled = false;
                } else {
                    field.children[1].children[1].classList.remove('is-hidden');
                    field.children[1].children[1].disabled = false;
                }
            },

            showEditCustomPlantAttribute: function(id, plant, label, datatype, content, is_global = false)
            {
                document.getElementById('edit-plant-attribute-attr').value = id;
                document.getElementById('edit-plant-attribute-plant').value = plant;
                document.getElementById('edit-plant-attribute-label').value = label;
                document.getElementById('edit-plant-attribute-datatype').value = datatype;

                let elFieldTarget = document.getElementById('field-custom-edit-attribute-content');

                if (datatype === 'bool') {
                    if (content == 1) {
                        elFieldTarget.children[1].children[0].children[0].children[1].children[0].children[0].checked = true;
                    } else {
                        elFieldTarget.children[1].children[0].children[0].children[2].children[0].children[0].checked = true;
                    }
                } else if (datatype === 'datetime') {
                    elFieldTarget.children[1].children[2].value = content;
                } else {
                    elFieldTarget.children[1].children[1].value = content;
                }

                window.vue.selectDataTypeInputField(document.querySelector('#edit-plant-attribute-datatype'), elFieldTarget);

                if (is_global) {
                    document.getElementById('field-custom-edit-attribute-datatype').style.display = 'none';
                    document.getElementById('plant-custom-attribute-removal-field').style.display = 'none';
                } else {
                    document.getElementById('field-custom-edit-attribute-datatype').style.display = 'inherit';
                    document.getElementById('plant-custom-attribute-removal-field').style.display = 'inherit';
                }

                window.vue.bShowEditCustomPlantAttribute = true;
            },

            removeCustomPlantAttribute: function(id, target)
            {
                window.vue.ajaxRequest('post', window.location.origin + '/plants/attributes/remove?id=' + id, {}, function(response){
                    if (response.code == 200) {
                        let elem = document.getElementById(target);
                        if (elem) {
                            elem.remove();
                        }
                        window.vue.bShowEditCustomPlantAttribute = false;
                    } else {
                        alert(response.msg);
                    }
                });
            },

            saveAllAttributes: function(source) {
                const forms = document.querySelector(source).getElementsByTagName('form');
                window.vue.bulkSubmitForm(0, forms, 100);
            },

            bulkSubmitForm: function(index, forms, delay) {
                if (index >= forms.length) {
                    alert(window.vue.operationSucceeded);
                    location.reload();
                    return;
                }

                const form = forms[index];

                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                });

                const formData = new FormData(form);

                setTimeout(function() {
                    fetch(form.action, {
                        method: form.method || 'POST',
                        body: formData
                    }).then(function(response){
                        return response.text();
                    }).then(function(data){
                        window.vue.bulkSubmitForm(index + 1, forms, delay);
                    }).catch(function(error){
                        console.error(error);
                        window.vue.bulkSubmitForm(index + 1, forms, delay);
                    });
                }, delay);
            },

            showEditPhoto: function(plant, property, hint = '')
            {
                document.getElementById('inpEditPhotoPlantId').value = plant;
                document.getElementById('inpEditPhotoAttribute').value = property;

                if (hint.length > 0) {
                    document.getElementById('inpEditPhotoHint').innerHTML = hint;
                }

                window.vue.bShowEditPhoto = true;
            },

            showPhotoUpload: function(plant)
            {
                document.getElementById('inpUploadPhotoPlantId').value = plant;
                window.vue.bShowUploadPhoto = true;
            },

            removePlantPreviewPhoto: function(plant, target) {
                window.vue.ajaxRequest('post', window.location.origin + '/plants/details/photo/remove', { plant: plant }, function(response){
                    if (response.code == 200) {
                        let elem = document.querySelector(target);
                        if (elem) {
                            elem.style.backgroundImage = 'url(' + response.placeholder + ')';
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            deletePhoto: function(photo, plant, target)
            {
                if (!confirm(window.vue.confirmPhotoRemoval)) {
                    return;
                }

                window.vue.ajaxRequest('post', window.location.origin + '/plants/details/gallery/photo/remove', { photo: photo, plant: plant }, function(response){
                    if (response.code == 200) {
                        let elem = document.getElementById(target);
                        if (elem) {
                            elem.remove();
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            showAddPlantAttachment: function(plant, anchor = '') {
                document.getElementById('inpAddPlantAttachmentPlantId').value = plant;
                document.getElementById('inpAddPlantAttachmentAnchor').value = anchor;
                window.vue.bShowAddPlantAttachment = true;
            },

            showEditPlantAttachment: function(id, plant, content, anchor = '') {
                document.getElementById('inpEditPlantAttachmentItemId').value = id;
                document.getElementById('inpEditPlantAttachmentPlantId').value = plant;
                document.getElementById('inpEditPlantAttachmentLabel').value = content;
                document.getElementById('inpEditPlantAttachmentAnchor').value = anchor;
                window.vue.bShowEditPlantAttachment = true;
            },

            removePlantAttachment: function(id, table_entry) {
                window.vue.ajaxRequest('post', window.location.origin + '/plants/attachments/remove', { item: id }, function(response) {
                    if (response.code == 200) {
                        document.getElementById(table_entry).remove();
                    } else {
                        alert(response.msg);
                    }
                });
            },

            loadNextPlantAttachments: function(obj, plant, table) {
                window.vue.ajaxRequest('post', window.location.origin + '/plants/attachments/fetch', { plant: plant, paginate: obj.dataset.paginate }, function(response) {
                    if (response.code == 200) {
                        let tbody = table.getElementsByTagName('tbody')[0];

                        response.data.forEach(function(elem, index) {
                            let newRow = document.createElement('tr');
                            newRow.id = 'plant-attachment-table-row-' + elem.id;
                            newRow.innerHTML = `
                                <td id="plant-attachment-item-` + elem.id + `"><a href="` + window.location.origin + '/attachments/' + elem.file + `">` + elem.label + `</a></td>
                                <td>` + elem.created_at + ` / ` + elem.updated_at + `</td>
                                <td>
                                    <span class="float-right">
                                        <span><a href="javascript:void(0);" onclick="window.vue.showEditPlantAttachment('` + elem.id + `', '` + plant + `', document.getElementById('plant-attachment-item-` + elem.id + `').innerText, 'plant-attachment-anchor');"><i class="fas fa-edit is-color-darker"></i></a></span>&nbsp;<span class="float-right"><a href="javascript:void(0);" onclick="if (confirm('` + window.vue.confirmRemovePlantAttachment + `')) { window.vue.removePlantAttachment('` + elem.id + `', 'plant-attachment-table-row-` + elem.id + `'); }"><i class="fas fa-trash-alt is-color-darker"></i></a></span>
                                    </span>
                                </td>
                            `;

                            tbody.appendChild(newRow);
                        });

                        obj.parentNode.parentNode.remove();

                        let actionRow = document.createElement('tr');
                        actionRow.id = 'plant-attachments-load-more';
                        actionRow.classList.add('plant-attachments-paginate');
                        actionRow.innerHTML = `<td colspan="3"><a href="javascript:void(0);" onclick="window.vue.loadNextPlantAttachments(this, '` + plant + `', document.getElementById('plant-attachments-table'));" data-paginate="` + response.data[response.data.length - 1].id + `">` + window.vue.loadMore + `</a></td>`;
                        tbody.appendChild(actionRow);
                    } else {
                        alert(response.msg);
                    }
                });
            },

            todayDateString: function() {
                let now = new Date();
                let month = String(now.getMonth() + 1).padStart(2, '0');
                let day = String(now.getDate()).padStart(2, '0');
                return now.getFullYear() + '-' + month + '-' + day;
            },

            showAddPlantLogEntry: function(plant, anchor = '') {
                document.getElementById('frmAddPlantLogEntry').reset();
                document.getElementById('inpAddPlantLogEntryPlantId').value = plant;
                document.getElementById('inpAddPlantLogEntryAnchor').value = anchor;
                document.getElementById('inpAddPlantLogEntryDate').value = window.vue.todayDateString();
                window.vue.bShowAddPlantLogEntry = true;
            },

            showEditPlantLogEntry: function(id, plant, title, content, tags = '', entryDate = '', photos = [], anchor = '') {
                document.getElementById('inpEditPlantLogEntryItemId').value = id;
                document.getElementById('inpEditPlantLogEntryPlantId').value = plant;
                document.getElementById('inpEditPlantLogEntryTitle').value = title || '';
                document.getElementById('inpEditPlantLogEntryContent').value = content || '';
                document.getElementById('inpEditPlantLogEntryTags').value = tags || '';
                document.getElementById('inpEditPlantLogEntryDate').value = entryDate || window.vue.todayDateString();
                document.getElementById('inpEditPlantLogEntryAnchor').value = anchor;
                document.getElementById('inpEditPlantLogEntryRemovePhotos').value = '';

                // Mark-attribute checkboxes are one-shot triggers, never persisted
                // state, so they always reset to unchecked when the modal opens.
                document.getElementById('frmEditPlantLogEntry').querySelectorAll('input[name="mark_watered"], input[name="mark_repotted"], input[name="mark_fertilised"]').forEach(function(cb) {
                    cb.checked = false;
                });

                let currentPhotosField = document.getElementById('edit-plant-log-entry-current-photos');
                let grid = document.getElementById('edit-plant-log-entry-current-photos-grid');
                grid.innerHTML = '';

                if (photos && photos.length > 0) {
                    photos.forEach(function(photo) {
                        let thumb = document.createElement('div');
                        thumb.className = 'plant-journal-modal-photo-thumb';
                        thumb.dataset.photoId = photo.id;

                        let img = document.createElement('img');
                        img.src = window.location.origin + '/img/' + photo.thumb;
                        img.alt = 'photo';
                        thumb.appendChild(img);

                        let removeBadge = document.createElement('span');
                        removeBadge.className = 'plant-journal-modal-photo-remove-badge';
                        removeBadge.innerHTML = '<i class="fas fa-times"></i>';
                        thumb.appendChild(removeBadge);

                        thumb.onclick = function() {
                            thumb.classList.toggle('is-marked-for-removal');
                            window.vue.syncRemovePhotosField();
                        };

                        grid.appendChild(thumb);
                    });
                    currentPhotosField.classList.remove('is-hidden');
                } else {
                    currentPhotosField.classList.add('is-hidden');
                }

                window.vue.bShowEditPlantLogEntry = true;
            },

            syncRemovePhotosField: function() {
                let grid = document.getElementById('edit-plant-log-entry-current-photos-grid');
                if (!grid) {
                    return;
                }

                let ids = [];
                grid.querySelectorAll('.plant-journal-modal-photo-thumb.is-marked-for-removal').forEach(function(thumb) {
                    ids.push(thumb.dataset.photoId);
                });

                document.getElementById('inpEditPlantLogEntryRemovePhotos').value = ids.join(',');
            },

            removePlantLogEntry: function(id, table_entry) {
                window.vue.ajaxRequest('post', window.location.origin + '/plants/log/remove', { item: id }, function(response) {
                    if (response.code == 200) {
                        document.getElementById(table_entry).remove();
                    } else {
                        alert(response.msg);
                    }
                });
            },

            togglePlantJournalSystemEntries: function(show) {
                let container = document.getElementById('plant-journal-entries');
                if (container) {
                    if (show) {
                        container.classList.remove('hide-system-entries');
                    } else {
                        container.classList.add('hide-system-entries');
                    }
                }

                window.vue.setCookieValue('plant_journal_show_system', show ? '1' : '0');
            },

            initPlantJournalSystemToggle: function() {
                let toggle = document.getElementById('plant-journal-toggle-system');
                if (!toggle) {
                    return;
                }

                let show = window.vue.getCookieValue('plant_journal_show_system', '0') == '1';
                toggle.checked = show;
                window.vue.togglePlantJournalSystemEntries(show);
            },

            loadNextPlantLogEntries: function(obj, plant, container) {
                window.vue.ajaxRequest('post', window.location.origin + '/plants/log/fetch', { plant: plant, paginate: obj.dataset.paginate, paginate_date: obj.dataset.paginateDate }, function(response) {
                    if (response.code == 200) {
                        response.data.forEach(function(elem, index) {
                            let entry = document.createElement('div');
                            entry.className = 'plant-journal-entry' + ((elem.is_system == 1) ? ' is-system' : '');
                            entry.id = 'plant-log-entry-table-row-' + elem.id;

                            let photos = elem.photos || [];
                            if (photos.length > 0) {
                                let photosDiv = document.createElement('div');
                                photosDiv.className = 'plant-journal-entry-photos';

                                photos.forEach(function(photo) {
                                    let photoLink = document.createElement('a');
                                    photoLink.href = window.location.origin + '/img/' + photo.original;
                                    photoLink.target = '_blank';
                                    photoLink.className = 'plant-journal-entry-photo';

                                    let img = document.createElement('img');
                                    img.src = window.location.origin + '/img/' + photo.thumb;
                                    img.alt = 'photo';

                                    photoLink.appendChild(img);
                                    photosDiv.appendChild(photoLink);
                                });

                                entry.appendChild(photosDiv);
                            }

                            let body = document.createElement('div');
                            body.className = 'plant-journal-entry-body';

                            let header = document.createElement('div');
                            header.className = 'plant-journal-entry-header';

                            let titleSpan = document.createElement('span');
                            titleSpan.className = 'plant-journal-entry-title';
                            titleSpan.id = 'plant-log-entry-item-' + elem.id;
                            titleSpan.textContent = elem.title;
                            header.appendChild(titleSpan);

                            if (elem.is_system == 1) {
                                let badge = document.createElement('span');
                                badge.className = 'plant-journal-entry-system-badge';
                                badge.textContent = window.vue.plantJournalSystemBadge;
                                header.appendChild(badge);
                            }

                            body.appendChild(header);

                            if (elem.content && elem.content.trim().length > 0) {
                                let contentDiv = document.createElement('div');
                                contentDiv.className = 'plant-journal-entry-content';
                                contentDiv.textContent = elem.content;
                                body.appendChild(contentDiv);
                            }

                            let tagWords = (elem.tags && elem.tags.trim().length > 0) ? elem.tags.trim().split(/\s+/) : [];
                            if (tagWords.length > 0) {
                                let tagsDiv = document.createElement('div');
                                tagsDiv.className = 'plant-journal-entry-tags';
                                tagWords.forEach(function(tag) {
                                    let tagSpan = document.createElement('span');
                                    tagSpan.className = 'plant-journal-entry-tag';
                                    tagSpan.textContent = tag;
                                    tagsDiv.appendChild(tagSpan);
                                });
                                body.appendChild(tagsDiv);
                            }

                            let footer = document.createElement('div');
                            footer.className = 'plant-journal-entry-footer';

                            let dateSpan = document.createElement('span');
                            dateSpan.className = 'plant-journal-entry-date';
                            dateSpan.textContent = elem.entry_date || elem.created_at;
                            footer.appendChild(dateSpan);

                            let actionsSpan = document.createElement('span');
                            actionsSpan.className = 'plant-journal-entry-actions';

                            let editLink = document.createElement('a');
                            editLink.href = 'javascript:void(0);';
                            editLink.innerHTML = '<i class="fas fa-edit is-color-darker"></i>';
                            editLink.onclick = function() {
                                window.vue.showEditPlantLogEntry(elem.id, plant, elem.title, elem.content, elem.tags, elem.entry_date, photos, 'plant-journal-anchor');
                            };

                            let removeLink = document.createElement('a');
                            removeLink.href = 'javascript:void(0);';
                            removeLink.style.marginLeft = '8px';
                            removeLink.innerHTML = '<i class="fas fa-trash-alt is-color-darker"></i>';
                            removeLink.onclick = function() {
                                if (confirm(window.vue.confirmRemovePlantLogEntry)) {
                                    window.vue.removePlantLogEntry(elem.id, entry.id);
                                }
                            };

                            actionsSpan.appendChild(editLink);
                            actionsSpan.appendChild(removeLink);
                            footer.appendChild(actionsSpan);

                            body.appendChild(footer);
                            entry.appendChild(body);

                            container.appendChild(entry);
                        });

                        obj.parentNode.remove();

                        if (response.data.length > 0) {
                            let paginateDiv = document.createElement('div');
                            paginateDiv.id = 'plant-log-load-more';
                            paginateDiv.className = 'plant-journal-paginate';

                            let loadMoreLink = document.createElement('a');
                            loadMoreLink.href = 'javascript:void(0);';
                            loadMoreLink.textContent = window.vue.loadMore;
                            loadMoreLink.dataset.paginate = response.data[response.data.length - 1].id;
                            loadMoreLink.dataset.paginateDate = response.data[response.data.length - 1].entry_date;
                            loadMoreLink.onclick = function() {
                                window.vue.loadNextPlantLogEntries(loadMoreLink, plant, document.getElementById('plant-journal-entries'));
                            };

                            paginateDiv.appendChild(loadMoreLink);
                            container.appendChild(paginateDiv);
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            buildLocationJournalPlantsChecklist: function(containerId, checkedIds = []) {
                let container = document.getElementById(containerId);
                if (!container) {
                    return;
                }

                container.innerHTML = '';

                let plants = [];
                let addBtn = document.getElementById('location-journal-add-btn');
                if (addBtn && addBtn.dataset.plants) {
                    try {
                        plants = JSON.parse(addBtn.dataset.plants) || [];
                    } catch (e) {
                        plants = [];
                    }
                }

                let checkedStrs = (checkedIds || []).map(function(v) { return String(v); });

                plants.forEach(function(plant) {
                    let label = document.createElement('label');

                    let checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.value = plant.id;
                    checkbox.dataset.plantCheckbox = '1';
                    checkbox.checked = checkedStrs.indexOf(String(plant.id)) !== -1;

                    label.appendChild(checkbox);
                    label.appendChild(document.createTextNode(' ' + plant.name));
                    container.appendChild(label);
                });
            },

            syncLocationJournalPlantsField: function(mode) {
                let containerId = (mode === 'add') ? 'add-location-log-entry-plants-checklist' : 'edit-location-log-entry-plants-checklist';
                let fieldId = (mode === 'add') ? 'inpAddLocationLogEntryApplyPlants' : 'inpEditLocationLogEntryApplyPlants';

                let container = document.getElementById(containerId);
                let field = document.getElementById(fieldId);
                if ((!container) || (!field)) {
                    return;
                }

                let ids = [];
                container.querySelectorAll('input[data-plant-checkbox]:checked').forEach(function(cb) {
                    ids.push(cb.value);
                });

                field.value = ids.join(',');
            },

            showAddLocationLogEntry: function(location, anchor = '') {
                document.getElementById('frmAddLocationLogEntry').reset();
                document.getElementById('inpAddLocationLogEntryLocationId').value = location;
                document.getElementById('inpAddLocationLogEntryAnchor').value = anchor;
                document.getElementById('inpAddLocationLogEntryDate').value = window.vue.todayDateString();
                document.getElementById('inpAddLocationLogEntryApplyPlants').value = '';
                window.vue.buildLocationJournalPlantsChecklist('add-location-log-entry-plants-checklist', []);
                window.vue.bShowAddLocationLogEntry = true;
            },

            showEditLocationLogEntry: function(id, location, title, content, tags = '', entryDate = '', photos = [], appliedPlantIds = [], anchor = '') {
                document.getElementById('inpEditLocationLogEntryItemId').value = id;
                document.getElementById('inpEditLocationLogEntryLocationId').value = location;
                document.getElementById('inpEditLocationLogEntryTitle').value = title || '';
                document.getElementById('inpEditLocationLogEntryContent').value = content || '';
                document.getElementById('inpEditLocationLogEntryTags').value = tags || '';
                document.getElementById('inpEditLocationLogEntryDate').value = entryDate || window.vue.todayDateString();
                document.getElementById('inpEditLocationLogEntryAnchor').value = anchor;
                document.getElementById('inpEditLocationLogEntryRemovePhotos').value = '';
                document.getElementById('inpEditLocationLogEntryApplyPlants').value = '';

                window.vue.buildLocationJournalPlantsChecklist('edit-location-log-entry-plants-checklist', appliedPlantIds || []);

                document.getElementById('frmEditLocationLogEntry').querySelectorAll('input[name="mark_watered"], input[name="mark_repotted"], input[name="mark_fertilised"]').forEach(function(cb) {
                    cb.checked = false;
                });

                let currentPhotosField = document.getElementById('edit-location-log-entry-current-photos');
                let grid = document.getElementById('edit-location-log-entry-current-photos-grid');
                grid.innerHTML = '';

                if (photos && photos.length > 0) {
                    photos.forEach(function(photo) {
                        let thumb = document.createElement('div');
                        thumb.className = 'plant-journal-modal-photo-thumb';
                        thumb.dataset.photoId = photo.id;

                        let img = document.createElement('img');
                        img.src = window.location.origin + '/img/' + photo.thumb;
                        img.alt = 'photo';
                        thumb.appendChild(img);

                        let removeBadge = document.createElement('span');
                        removeBadge.className = 'plant-journal-modal-photo-remove-badge';
                        removeBadge.innerHTML = '<i class="fas fa-times"></i>';
                        thumb.appendChild(removeBadge);

                        thumb.onclick = function() {
                            thumb.classList.toggle('is-marked-for-removal');
                            window.vue.syncRemoveLocationLogPhotosField();
                        };

                        grid.appendChild(thumb);
                    });
                    currentPhotosField.classList.remove('is-hidden');
                } else {
                    currentPhotosField.classList.add('is-hidden');
                }

                window.vue.bShowEditLocationLogEntry = true;
            },

            syncRemoveLocationLogPhotosField: function() {
                let grid = document.getElementById('edit-location-log-entry-current-photos-grid');
                if (!grid) {
                    return;
                }

                let ids = [];
                grid.querySelectorAll('.plant-journal-modal-photo-thumb.is-marked-for-removal').forEach(function(thumb) {
                    ids.push(thumb.dataset.photoId);
                });

                document.getElementById('inpEditLocationLogEntryRemovePhotos').value = ids.join(',');
            },

            removeLocationLogEntry: function(id, table_entry) {
                window.vue.ajaxRequest('post', window.location.origin + '/plants/location/log/remove', { item: id }, function(response) {
                    if (response.code == 200) {
                        document.getElementById(table_entry).remove();
                    } else {
                        alert(response.msg);
                    }
                });
            },

            toggleLocationJournalSystemEntries: function(show) {
                let container = document.getElementById('location-journal-entries');
                if (container) {
                    if (show) {
                        container.classList.remove('hide-system-entries');
                    } else {
                        container.classList.add('hide-system-entries');
                    }
                }

                window.vue.setCookieValue('location_journal_show_system', show ? '1' : '0');
            },

            initLocationJournalSystemToggle: function() {
                let toggle = document.getElementById('location-journal-toggle-system');
                if (!toggle) {
                    return;
                }

                let show = window.vue.getCookieValue('location_journal_show_system', '0') == '1';
                toggle.checked = show;
                window.vue.toggleLocationJournalSystemEntries(show);
            },

            loadNextLocationLogEntries: function(obj, location, container) {
                window.vue.ajaxRequest('post', window.location.origin + '/plants/location/log/fetch', { location: location, paginate: obj.dataset.paginate, paginate_date: obj.dataset.paginateDate }, function(response) {
                    if (response.code == 200) {
                        response.data.forEach(function(elem, index) {
                            let entry = document.createElement('div');
                            entry.className = 'plant-journal-entry' + ((elem.is_system == 1) ? ' is-system' : '');
                            entry.id = 'location-log-entry-table-row-' + elem.id;

                            let photos = elem.photos || [];
                            if (photos.length > 0) {
                                let photosDiv = document.createElement('div');
                                photosDiv.className = 'plant-journal-entry-photos';

                                photos.forEach(function(photo) {
                                    let photoLink = document.createElement('a');
                                    photoLink.href = window.location.origin + '/img/' + photo.original;
                                    photoLink.target = '_blank';
                                    photoLink.className = 'plant-journal-entry-photo';

                                    let img = document.createElement('img');
                                    img.src = window.location.origin + '/img/' + photo.thumb;
                                    img.alt = 'photo';

                                    photoLink.appendChild(img);
                                    photosDiv.appendChild(photoLink);
                                });

                                entry.appendChild(photosDiv);
                            }

                            let body = document.createElement('div');
                            body.className = 'plant-journal-entry-body';

                            let header = document.createElement('div');
                            header.className = 'plant-journal-entry-header';

                            let titleSpan = document.createElement('span');
                            titleSpan.className = 'plant-journal-entry-title';
                            titleSpan.id = 'location-log-entry-item-' + elem.id;
                            titleSpan.textContent = elem.title;
                            header.appendChild(titleSpan);

                            if (elem.is_system == 1) {
                                let badge = document.createElement('span');
                                badge.className = 'plant-journal-entry-system-badge';
                                badge.textContent = window.vue.plantJournalSystemBadge;
                                header.appendChild(badge);
                            }

                            body.appendChild(header);

                            if (elem.content && elem.content.trim().length > 0) {
                                let contentDiv = document.createElement('div');
                                contentDiv.className = 'plant-journal-entry-content';
                                contentDiv.textContent = elem.content;
                                body.appendChild(contentDiv);
                            }

                            let tagWords = (elem.tags && elem.tags.trim().length > 0) ? elem.tags.trim().split(/\s+/) : [];
                            if (tagWords.length > 0) {
                                let tagsDiv = document.createElement('div');
                                tagsDiv.className = 'plant-journal-entry-tags';
                                tagWords.forEach(function(tag) {
                                    let tagSpan = document.createElement('span');
                                    tagSpan.className = 'plant-journal-entry-tag';
                                    tagSpan.textContent = tag;
                                    tagsDiv.appendChild(tagSpan);
                                });
                                body.appendChild(tagsDiv);
                            }

                            let plantIds = elem.plants || [];
                            if (plantIds.length > 0) {
                                let addBtn = document.getElementById('location-journal-add-btn');
                                let allPlants = [];
                                if (addBtn && addBtn.dataset.plants) {
                                    try {
                                        allPlants = JSON.parse(addBtn.dataset.plants) || [];
                                    } catch (e) {
                                        allPlants = [];
                                    }
                                }

                                let plantsDiv = document.createElement('div');
                                plantsDiv.className = 'plant-journal-entry-tags';
                                plantIds.forEach(function(plantId) {
                                    let match = allPlants.find(function(p) { return String(p.id) === String(plantId); });
                                    if (match) {
                                        let chip = document.createElement('span');
                                        chip.className = 'plant-journal-entry-plant-chip';
                                        chip.innerHTML = '<i class="fas fa-seedling"></i>&nbsp;';
                                        chip.appendChild(document.createTextNode(match.name));
                                        plantsDiv.appendChild(chip);
                                    }
                                });
                                body.appendChild(plantsDiv);
                            }

                            let footer = document.createElement('div');
                            footer.className = 'plant-journal-entry-footer';

                            let dateSpan = document.createElement('span');
                            dateSpan.className = 'plant-journal-entry-date';
                            dateSpan.textContent = elem.entry_date || elem.created_at;
                            footer.appendChild(dateSpan);

                            let actionsSpan = document.createElement('span');
                            actionsSpan.className = 'plant-journal-entry-actions';

                            let editLink = document.createElement('a');
                            editLink.href = 'javascript:void(0);';
                            editLink.innerHTML = '<i class="fas fa-edit is-color-darker"></i>';
                            editLink.onclick = function() {
                                window.vue.showEditLocationLogEntry(elem.id, location, elem.title, elem.content, elem.tags, elem.entry_date, photos, plantIds, 'location-journal-anchor');
                            };

                            let removeLink = document.createElement('a');
                            removeLink.href = 'javascript:void(0);';
                            removeLink.style.marginLeft = '8px';
                            removeLink.innerHTML = '<i class="fas fa-trash-alt is-color-darker"></i>';
                            removeLink.onclick = function() {
                                if (confirm(window.vue.confirmRemoveLocationLogEntry)) {
                                    window.vue.removeLocationLogEntry(elem.id, entry.id);
                                }
                            };

                            actionsSpan.appendChild(editLink);
                            actionsSpan.appendChild(removeLink);
                            footer.appendChild(actionsSpan);

                            body.appendChild(footer);
                            entry.appendChild(body);

                            container.appendChild(entry);
                        });

                        obj.parentNode.remove();

                        if (response.data.length > 0) {
                            let paginateDiv = document.createElement('div');
                            paginateDiv.id = 'location-log-load-more';
                            paginateDiv.className = 'plant-journal-paginate';

                            let loadMoreLink = document.createElement('a');
                            loadMoreLink.href = 'javascript:void(0);';
                            loadMoreLink.textContent = window.vue.loadMore;
                            loadMoreLink.dataset.paginate = response.data[response.data.length - 1].id;
                            loadMoreLink.dataset.paginateDate = response.data[response.data.length - 1].entry_date;
                            loadMoreLink.onclick = function() {
                                window.vue.loadNextLocationLogEntries(loadMoreLink, location, document.getElementById('location-journal-entries'));
                            };

                            paginateDiv.appendChild(loadMoreLink);
                            container.appendChild(paginateDiv);
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            markHistorical: function(plant) {
                if (!confirm(window.vue.confirmPlantAddHistory)) {
                    return;
                }

                location.href = window.location.origin + '/plants/history/add?plant=' + plant;
            },

            unmarkHistorical: function(plant) {
                if (!confirm(window.vue.confirmPlantRemoveHistory)) {
                    return;
                }

                location.href = window.location.origin + '/plants/history/remove?plant=' + plant;
            },

            deletePlant: function(plant, retloc)
            {
                if (!confirm(window.vue.confirmPlantRemoval)) {
                    return;
                }

                location.href = window.location.origin + '/plants/remove?plant=' + plant + '&location=' + retloc;
            },

            toggleTaskStatus: function(id)
            {
                window.vue.ajaxRequest('post', window.location.origin + '/tasks/toggle', { task: id }, function(response){
                    if (response.code == 200) {
                        let elem = document.getElementById('task-item-' + id);
                        if (elem) {
                            elem.remove();
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            editTask: function(id)
            {
                document.getElementById('inpEditTaskId').value = id;
                document.getElementById('inpEditTaskTitle').value = document.getElementById('task-item-title-' + id).childNodes[1].textContent;
                document.getElementById('inpEditTaskDescription').value = document.getElementById('task-item-description-' + id).innerText;

                let dueDate = document.getElementById('task-item-due-' + id);
                if ((dueDate) && (dueDate.childNodes.length > 0)) {
                    document.getElementById('inpEditTaskDueDate').value = dueDate.childNodes[0].innerText;
                    document.getElementById('inpEditTaskRecurringTime').value = (typeof dueDate.childNodes[2] !== 'undefined') ? dueDate.childNodes[2].dataset.time : '';
                    document.getElementById('inpEditTaskRecurringScope').value = (typeof dueDate.childNodes[2] !== 'undefined') ? dueDate.childNodes[2].dataset.scope : '';

                    document.getElementById('edit-recurring-flag').classList.remove('is-hidden');
                    document.getElementById('edit-recurring-time').classList.remove('is-hidden');

                    document.getElementById('inpEditTaskRecurringFlag').checked = document.getElementById('inpEditTaskRecurringTime').value.length > 0;
                    if (!document.getElementById('inpEditTaskRecurringFlag').checked) {
                        document.getElementById('edit-recurring-time').classList.add('is-hidden');
                    }
                } else {
                    document.getElementById('inpEditTaskDueDate').value = '';

                    if (!document.getElementById('edit-recurring-flag').classList.contains('is-hidden')) {
                        document.getElementById('edit-recurring-flag').classList.add('is-hidden');
                    }

                    if (!document.getElementById('edit-recurring-time').classList.contains('is-hidden')) {
                        document.getElementById('edit-recurring-time').classList.add('is-hidden');
                    }
                }

                window.vue.bShowEditTask = true;
            },

            removeTask: function(id)
            {
                window.vue.ajaxRequest('post', window.location.origin + '/tasks/remove', { task: id }, function(response){
                    if (response.code == 200) {
                        let elem = document.getElementById('task-item-' + id);
                        if (elem) {
                            elem.remove();
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            updateLastWatered: function(id)
            {
                if (!confirm(window.vue.confirmSetAllWatered)) {
                    return;
                }

                location.href = window.location.origin + '/plants/location/' + id + '/water';
            },

            updateLastRepotted: function(id)
            {
                if (!confirm(window.vue.confirmSetAllRepotted)) {
                    return;
                }

                location.href = window.location.origin + '/plants/location/' + id + '/repot';
            },

            updateLastFertilised: function(id)
            {
                if (!confirm(window.vue.confirmSetAllFertilised)) {
                    return;
                }

                location.href = window.location.origin + '/plants/location/' + id + '/fertilise';
            },

            expandInventoryItem: function(id)
            {
                let elem = document.getElementById(id);
                if (elem) {
                    elem.classList.toggle('expand');
                }
            },

            incrementInventoryItem: function(id, target)
            {
                window.vue.ajaxRequest('get', window.location.origin + '/inventory/amount/increment?id=' + id, {}, function(response) {
                    if (response.code == 200) {
                        let elem = document.getElementById(target);
                        if (elem) {
                            elem.innerHTML = response.amount;

                            if (response.amount == 0) {
                                elem.classList.add('is-inventory-item-empty');
                            } else {
                                elem.classList.remove('is-inventory-item-empty');
                            }
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            decrementInventoryItem: function(id, target)
            {
                window.vue.ajaxRequest('get', window.location.origin + '/inventory/amount/decrement?id=' + id, {}, function(response) {
                    if (response.code == 200) {
                        let elem = document.getElementById(target);
                        if (elem) {
                            elem.innerHTML = response.amount;

                            if (response.amount == 0) {
                                elem.classList.add('is-inventory-item-empty');
                            } else {
                                elem.classList.remove('is-inventory-item-empty');
                            }
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            editInventoryItem: function(id, name, group, location, description, tags, amount)
            {
                document.getElementById('inpInventoryItemId').value = id;
                document.getElementById('inpInventoryItemName').value = document.getElementById(name).children[1].innerText;
                document.getElementById('inpInventoryItemGroup').value = group;
                document.getElementById('inpInventoryItemLocation').value = document.getElementById(location).children[0].innerText;
                document.getElementById('inpInventoryItemDescription').value = document.getElementById(description).innerText;
                document.getElementById('inpInventoryItemTags').value = document.getElementById(tags).innerText;
                document.getElementById('inpInventoryItemAmount').value = amount;

                window.vue.bShowEditInventoryItem = true;
            },

            deleteInventoryItem: function(id, target)
            {
                if (!confirm(window.vue.confirmInventoryItemRemoval)) {
                    return;
                }

                window.vue.ajaxRequest('get', window.location.origin + '/inventory/remove?id=' + id, {}, function(response) {
                    if (response.code == 200) {
                        let elem = document.getElementById(target);
                        if (elem) {
                            elem.remove();
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            createInventoryGroup: function(token, label, tbody, button)
            {
                window.vue.ajaxRequest('post', window.location.origin + '/inventory/group/add', { token: token, label: label }, function(response) {
                    if (response.code == 200) {
                        button.innerText = window.vue.addItem;

                        let newRow = document.createElement('tr');
                        newRow.id = 'inventory-group-item-' + response.itemid;
                        newRow.innerHTML = `
                            <td><a href="javascript:void(0);" id="inventory-group-elem-token-` + response.itemid + `" onclick="window.vue.editInventoryGroupItem(` + response.itemid + `, 'token', document.getElementById('inventory-group-elem-token-` + response.itemid + `').innerText);">` + response.token + `</a></td>
                            <td><a href="javascript:void(0);" id="inventory-group-elem-label-` + response.itemid + `" onclick="window.vue.editInventoryGroupItem(` + response.itemid + `, 'label', document.getElementById('inventory-group-elem-label-` + response.itemid + `').innerText);">` + response.label + `</a></td>
                            <td><a href="javascript:void(0);" onclick="window.vue.removeInventoryGroupItem(` + response.itemid + `, 'inventory-group-item-` + response.itemid + `');"><i class="fas fa-times"></i></a></td>
                        `;

                        tbody.appendChild(newRow);

                        window.vue.bInvGroupChanges = true;
                    } else {
                        button.innerText = window.vue.addItem;
                        alert(response.msg);
                    }
                });
            },

            editInventoryGroupItem: function(id, what, def)
            {
                let input = prompt(what, def);

                if (input.length > 0) {
                    window.vue.ajaxRequest('post', window.location.origin + '/inventory/group/edit', {
                        id: id,
                        what: what,
                        value: input
                    }, function(response) {
                        if (response.code == 200) {
                            if (what === 'token') {
                                document.getElementById('inventory-group-elem-token-' + id).innerText = input;
                            } else if (what === 'label') {
                                document.getElementById('inventory-group-elem-label-' + id).innerText = input;
                            }

                            window.vue.bInvGroupChanges = true;
                        } else {
                            alert(response.msg);
                        }
                    });
                }
            },

            removeInventoryGroupItem: function(id, target)
            {
                if (!confirm(window.vue.confirmInventoryItemRemoval)) {
                    return;
                }

                window.vue.ajaxRequest('get', window.location.origin + '/inventory/group/remove?id=' + id, {}, function(response) {
                    if (response.code == 200) {
                        let elem = document.getElementById(target);
                        if (elem) {
                            elem.remove();
                        }

                        window.vue.bInvGroupChanges = true;
                    } else {
                        alert(response.msg);
                    }
                });
            },

            showInvGroupModal: function() {
                window.vue.bInvGroupChanges = false;
                window.vue.bShowManageGroups = true;
            },

            closeInvGroupModal: function() {
                window.vue.bShowManageGroups = false;

                if (window.vue.bInvGroupChanges) {
                    location.reload();
                }
            },

            refreshChat: function(auth_user)
            {
                window.vue.ajaxRequest('get', window.location.origin + '/chat/query', {}, function(response) {
                    if (response.code == 200) {
                        response.messages.forEach(function(elem, index) {
                            document.getElementById('chat').innerHTML = window.vue.renderNewChatMessage(elem, auth_user) + document.getElementById('chat').innerHTML;
                        
                            window.vue.playAudio('new_message.wav');
                        });
                    }
                });

                setTimeout(window.vue.refreshChat, window.constChatMessageQueryRefreshRate);
            },

            renderNewChatMessage: function(elem, auth_user)
            {
                let chatmsgright = '';
                if (elem.userId == auth_user) {
                    chatmsgright = 'chat-message-right';
                }

                let html = '';

                if (!elem.system) {
                    html = `
                        <div class="chat-message ` + chatmsgright + `">
                            <div class="chat-message-user">
                                <div class="is-inline-block" style="color: ` + elem.chatcolor + `;">` + elem.userName + `</div>
                                <div class="chat-message-new">` + window.vue.newChatMessage + `</div>
                            </div>

                            <div class="chat-message-content">
                                <pre>` + elem.message + `</pre>
                            </div>

                            <div class="chat-message-info">
                                ` + elem.diffForHumans + `
                            </div>
                        </div>
                    `;
                } else {
                    html = `
                    <div class="system-message">
                        <div class="system-message-left system-message-left-new">
                            <div class="system-message-context">` + ((elem.userName) ? elem.userName : 'System') + ` @ ` + elem.created_at + `</div>
                            
                            <div class="system-message-content">` + elem.message + `</div>
                        </div>

                        <div class="system-message-right">
                            <div class="system-message-new chat-message-new">` + window.vue.newChatMessage + `</div>
                        </div>
                    </div>
                    `;
                }

                return html;
            },

            refreshUserList: function()
            {
                window.vue.ajaxRequest('get', window.location.origin + '/chat/user/online', {}, function(response) {
                    if (response.code == 200) {
                        let target = document.getElementById('chat-user-list');
                        target.innerHTML = window.vue.currentlyOnline;

                        response.users.forEach(function(elem, index) {
                            let comma = '';
                            if (index < response.users.length - 1) {
                                comma = ', ';
                            }
                            
                            target.innerHTML += elem.name + comma;
                        });
                    }
                });

                setTimeout(window.vue.refreshUserList, window.constChatUserListRefreshRate);
            },

            refreshTypingStatus: function()
            {
                if (!window.vue.chatTypingEnable) {
                    return;
                }

                window.vue.ajaxRequest('get', window.location.origin + '/chat/typing/update', {}, function(response){
                    if (response.code != 200) {
                        console.error(response.msg);
                    }
                });

                setTimeout(function(){
                    window.vue.chatTypingTimer = null;
                }, 5000);
            },

            handleChatInput: function()
            {
                if (!window.vue.chatTypingTimer) {
                    window.vue.chatTypingTimer = setTimeout(function(){
                        window.vue.refreshTypingStatus();
                    }, 1000);
                }
            },

            handleTypingIndicator: function()
            {
                window.vue.ajaxRequest('get', window.location.origin + '/chat/typing', {}, function(response){
                    if (response.code == 200) {
                        if (response.status) {
                            let elem = document.getElementsByClassName('chat-typing-indicator')[0];
                            elem.style.display = 'block';

                            window.vue.chatTypingHide = setTimeout(window.vue.hideChatTypingIndicator, 6550);
                        }
                    }
                });

                setTimeout(window.vue.handleTypingIndicator, window.constChatTypingRefreshRate);
            },

            hideChatTypingIndicator: function()
            {
                if (window.vue.chatTypingHide !== null) {
                    let elem = document.getElementsByClassName('chat-typing-indicator')[0];
                    elem.style.display = 'none';

                    window.vue.chatTypingHide = null;
                }
            },

            animateChatTypingIndicator: function()
            {
                let indicator = document.getElementsByClassName('chat-typing-indicator')[0];
                if (indicator.style.display === 'block') {
                    window.vue.removePreviousChatIndicatorCircleStyle();

                    let elem = document.getElementById('chat-typing-circle-' + window.vue.chatTypingCounter.toString());
                    elem.style.color = 'rgb(50, 50, 50)';
                    elem.classList.add('fa-lg');

                    window.vue.chatTypingCounter++;
                    if (window.vue.chatTypingCounter > 3) {
                        window.vue.chatTypingCounter = 1;
                    }
                }

                setTimeout(window.vue.animateChatTypingIndicator, 350);
            },

            removePreviousChatIndicatorCircleStyle: function()
            {
                let previous = window.vue.chatTypingCounter - 1;
                if (previous == 0) {
                    previous = 3;
                }

                let elem = document.getElementById('chat-typing-circle-' + previous.toString());
                if (elem.classList.contains('fa-lg')) {
                    elem.classList.remove('fa-lg');
                    elem.style.color = 'inherit';
                }
            },

            fetchUnreadMessageCount: function(target) {
                window.vue.ajaxRequest('get', window.location.origin + '/chat/messages/count', {}, function(response) {
                    if (response.code == 200) {
                        if (response.count > 0) {
                            target.classList.remove('is-hidden');
                            target.children[0].innerText = response.count;
                        } else {
                            target.classList.add('is-hidden');
                        }
                    }
                });

                setTimeout(window.vue.fetchUnreadMessageCount.bind(null, target), window.constChatMessageQueryRefreshRate);
            },

            fetchNewSystemMessage: function(target) {
                window.vue.ajaxRequest('get', window.location.origin + '/chat/system/message/latest', {}, function(response) {
                    if (response.code == 200) {
                        if (response.message) {
                            window.vue.fadeSystemMessage(target, window.vue.renderNewSystemMessage(response.message), response.message.id);

                            window.vue.playAudio('new_message.wav');
                        }
                    }
                });

                setTimeout(window.vue.fetchNewSystemMessage.bind(null, target), window.constChatMessageQueryRefreshRate);
            },

            renderNewSystemMessage: function(elem) {
                let html = `
                    <div class="system-message-small fade fade-out" id="system-message-small-` + elem.id + `">
                        <div class="system-message-small-context">` + ((elem.userName) ? elem.userName : 'System') + ` @ ` + elem.created_at + `</div>

                        <div class="system-message-small-content">` + elem.message + `</div>
                    </div>
                `;

                return html;
            },

            fadeSystemMessage: function(target, code, id) {
                target.innerHTML = code + target.innerHTML;

                let fadeElem = document.getElementById('system-message-small-' + id);

                setTimeout(function() {
                    fadeElem.classList.replace('fade-out', 'fade-in');
                }, 250);
                
                setTimeout(function() {
                    fadeElem.classList.replace('fade-in', 'fade-out');
                }, 5000);
            },

            textFilterElements: function(token) {
                let elems = document.getElementsByClassName('plant-filter-text-target');
                for (let i = 0; i < elems.length; i++) {
                    let target = elems[i].parentNode;
                    
                    while (!target.classList.contains('plant-filter-text-root')) {
                        target = target.parentNode;
                    }

                    if (!elems[i].innerText.toLowerCase().includes(token.toLowerCase())) {
                        target.classList.add('is-hidden');
                    } else {
                        target.classList.remove('is-hidden');
                    }
                }
            },

            filterTasks: function(token) {
                let elems = document.getElementsByClassName('task');
                for (let i = 0; i < elems.length; i++) {
                    let elemTitle = elems[i].children[1].children[0];
                    let elemDescription = elems[i].children[2].children[0];

                    if ((elemTitle.innerText.toLowerCase().includes(token.toLowerCase())) || (elemDescription.innerText.toLowerCase().includes(token.toLowerCase()))) {
                        elems[i].classList.remove('is-hidden'); 
                    } else {
                        elems[i].classList.add('is-hidden'); 
                    }
                }
            },

            filterInventory: function(token) {
                let elems = document.getElementsByClassName('inventory-item');
                for (let i = 0; i < elems.length; i++) {
                    let elemName = elems[i].children[1].children[0];
                    let elemDescription = elems[i].children[2].children[1];
                    let elemTags = elems[i].children[2].children[0].children[0];
                    let elemLocation = elems[i].children[2].children[3].children[0];
                    
                    if ((elemName.innerText.toLowerCase().includes(token.toLowerCase())) || (elemDescription.innerText.toLowerCase().includes(token.toLowerCase())) || (elemTags.innerText.toLowerCase().includes(token.toLowerCase())) || (elemLocation.innerText.toLowerCase().includes(token.toLowerCase()))) {
                        elems[i].classList.remove('is-hidden'); 
                    } else {
                        elems[i].classList.add('is-hidden'); 
                    }
                }
            },

            toggleDropdown: function(elem, container) {
                if (elem.classList.contains('is-active')) {
                    elem.classList.remove('is-active');
                    container.classList.remove('plant-card-dropdown');
                } else {
                    elem.classList.add('is-active');
                    container.classList.add('plant-card-dropdown');
                }
            },

            selectAdminTab: function(tab) {
                const tabs = ['environment', 'media', 'users', 'locations', 'auth', 'attributes', 'calendar', 'mail', 'themes', 'backup', 'weather', 'api', 'info'];

                let selEl = document.querySelector('.admin-' + tab);
                if (selEl) {
                    tabs.forEach(function(elem, index) {
                        let otherEl = document.querySelector('.admin-' + elem);
                        if (otherEl) {
                            otherEl.classList.add('is-hidden');
                        }

                        let otherTabs = document.querySelector('.admin-tab-' + elem);
                        if (otherTabs) {
                            otherTabs.classList.remove('is-active');
                        }
                    });

                    selEl.classList.remove('is-hidden');

                    let selTab = document.querySelector('.admin-tab-' + tab);
                    if (selTab) {
                        selTab.classList.add('is-active');
                    }
                }
            },

            switchAdminTab: function(tab) {
                window.vue.selectAdminTab(tab);

                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                history.replaceState(null, '', url.toString());
            },

            showImagePreview: function(asset, aspect = 'is-2by3') {
                let img = document.getElementById('preview-image-modal-img');
                if (img) {
                    img.src = asset;

                    if (window.vue.clsLastImagePreviewAspect.length > 0) {
                        img.parentNode.classList.remove(window.vue.clsLastImagePreviewAspect);
                    }

                    window.vue.clsLastImagePreviewAspect = aspect;
                    img.parentNode.classList.add(window.vue.clsLastImagePreviewAspect);

                    window.vue.bShowPreviewImageModal = true;
                }
            },

            showSharePhoto: function(asset, title, type) {
                document.getElementById('share-photo-title').value = title;
                document.getElementById('share-photo-id').value = asset;
                document.getElementById('share-photo-type').value = type;

                document.getElementById('share-photo-result').classList.add('is-hidden');
                document.getElementById('share-photo-error').classList.add('is-hidden');
                document.getElementById('share-photo-submit-action').classList.remove('is-hidden');

                window.vue.bShowSharePhoto = true;
            },

            performPhotoShare: function(asset, title, _public, description, keywords, type, result, button, error) {
                let origButtonHtml = button.innerHTML;
                button.innerHTML = '<i class=\'fas fa-spinner fa-spin\'></i>&nbsp;' + window.vue.loadingPleaseWait;

                window.vue.ajaxRequest('post', window.location.origin + '/share/photo/post', { asset: asset, title: title, public: _public, description: description, keywords: keywords, type: type }, function(response) {
                    button.innerHTML = origButtonHtml;

                    if (response.code == 200) {
                        result.value = response.data.url;
                        result.parentNode.parentNode.classList.remove('is-hidden');
                        button.classList.add('is-hidden');
                        error.classList.add('is-hidden');
                    } else {
                        error.innerHTML = response.msg;
                        error.classList.remove('is-hidden');
                    }
                });
            },

            generateNewToken: function(target, button) {
                let oldTxt = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                window.vue.ajaxRequest('post', window.location.origin + '/admin/cronjob/token', {}, function(response) {
                    button.innerHTML = oldTxt;

                    if (response.code == 200) {
                        target.value = response.token;
                    } else {
                        alert(response.msg);
                    }
                });
            },

            startBackup: function(button, plants, gallery, tasks, inventory, calendar) {
                let oldText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp;' + oldText;
                button.disabled = true;

                window.vue.ajaxRequest('post', window.location.origin + '/export/start', {
                    plants: plants,
                    gallery: gallery,
                    tasks: tasks,
                    inventory: inventory,
                    calendar: calendar
                }, function(response) {
                    button.innerHTML = oldText;
                    button.disabled = false;

                    if (response.code == 200) {
                        let export_result = document.getElementById('export-result');
                        if (export_result) {
                            export_result.classList.remove('is-hidden');

                            export_result.children[1].href = response.file;
                            export_result.children[1].innerHTML = response.file;
                        }
                    }
                });
            },

            startImport: function(button, file, locations, plants, gallery, tasks, inventory, calendar) {
                let oldText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp;' + oldText;
                button.disabled = true;
                
                let formData = new FormData();
                formData.append('import', file.files[0]);
                formData.append('locations', ((locations) ? 1 : 0));
                formData.append('plants', ((plants) ? 1 : 0));
                formData.append('gallery', ((gallery) ? 1 : 0));
                formData.append('tasks', ((tasks) ? 1 : 0));
                formData.append('inventory', ((inventory) ? 1 : 0));
                formData.append('calendar', ((calendar) ? 1 : 0));

                window.vue.ajaxRequest('post', window.location.origin + '/import/start', formData, function(response) {
                    button.innerHTML = oldText;
                    button.disabled = false;

                    if (response.code == 200) {
                        let import_result = document.getElementById('import-result');
                        if (import_result) {
                            import_result.classList.remove('is-hidden');
                        }
                    }
                });
            },

            startThemeImport: function(file, button) {
                let oldText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp;' + oldText;
                
                let formData = new FormData();
                formData.append('theme', file.files[0]);

                window.vue.ajaxRequest('post', window.location.origin + '/admin/themes/import', formData, function(response) {
                    button.innerHTML = oldText;
                    
                    if (response.code == 200) {
                        let import_result = document.getElementById('themes-import-result');
                        if (import_result) {
                            import_result.innerText = import_result.innerText.replace('{count}', response.themes.length);
                            import_result.classList.remove('is-hidden');
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            removeTheme: function(theme) {
                window.vue.ajaxRequest('post', window.location.origin + '/admin/themes/remove', { theme: theme }, function(response) {
                    if (response.code == 200) {
                        let tableElem = document.getElementById('admin-themes-list-item-' + theme);
                        if (tableElem) {
                            tableElem.remove();
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            renderCalendar: function(elem, date_from, date_till = null) {
                window.vue.ajaxRequest('post', window.location.origin + '/calendar/query', { date_from: date_from, date_till: date_till }, function(response){
                    if (response.code == 200) {
                        let content = document.getElementById(elem);
                        if (content) {
                            let data = response.data;

                            let legendElem = document.getElementById('calendar-legend');
                            let contentInner = document.getElementById('calendar-content-inner');

                            if (!data.length) {
                                if (window.calendarChart !== null) {
                                    window.calendarChart.destroy();
                                    window.calendarChart = null;
                                }

                                if (legendElem) {
                                    legendElem.innerHTML = '';
                                }

                                if (contentInner) {
                                    let emptyHint = contentInner.dataset.emptyHint || 'No entries in this timespan';
                                    contentInner.style.height = '120px';
                                    contentInner.innerHTML = '<div class="calendar-empty-hint"></div><canvas id="' + elem + '"></canvas>';
                                    contentInner.querySelector('.calendar-empty-hint').textContent = emptyHint;
                                }

                                return;
                            }

                            if (contentInner && !document.getElementById(elem)) {
                                contentInner.innerHTML = '<canvas id="' + elem + '"></canvas>';
                                content = document.getElementById(elem);
                            }

                            if (contentInner) {
                                contentInner.style.height = Math.max(320, 70 + (data.length * 42)) + 'px';
                            }

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

                            data.sort(function (a, b) {
                                return new Date(a.date_from) - new Date(b.date_from);
                            });

                            const labels = data.map(x => {
                                return [x.name];
                            });

                            const newData = data.map(x => {
                                return [x.date_from.split(' ')[0], x.date_till.split(' ')[0], x.class_name, x.id, x.class_descriptor]
                            });
                            
                            let colorsBackground = [];
                            data.forEach(function(elem, index) {
                                colorsBackground.push(elem.color_background);
                            });
                            
                            let colorsBorder = [];
                            data.forEach(function(elem, index) {
                                colorsBorder.push(elem.color_border);
                            });

                            const config = {
                                type: 'bar',
                                data: {
                                    labels: labels,
                                    datasets: [
                                        {
                                            data: newData,
                                            backgroundColor: colorsBackground,
                                            borderColor: colorsBorder,
                                            borderWidth: 1,
                                            fill: false,
                                            barPercentage: 0.6,
                                            categoryPercentage: 0.7,
                                            minBarLength: 4
                                        }
                                    ]
                                },
                                plugins: [calendarWeekendShadingPlugin, calendarTodayLinePlugin],
                                options: {
                                    indexAxis: 'y',
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    layout: {
                                        padding: {
                                            top: 6,
                                            right: 16
                                        }
                                    },
                                    scales: {
                                        x: {
                                            min: response.date_from,
                                            max: response.date_till,
                                            type: 'time',
                                            position: 'top',
                                            time: {
                                                unit: 'day',
                                                displayFormats: {
                                                    day: 'EEE, MMM d'
                                                }
                                            },
                                            ticks: {
                                                color: 'rgb(190, 190, 190)',
                                                maxRotation: 45,
                                                minRotation: 0
                                            },
                                            grid: {
                                                color: function(ctx) {
                                                    let day = new Date(ctx.tick.value).getDay();
                                                    return (day === 0 || day === 6) ? 'rgba(255, 255, 255, 0.14)' : 'rgba(255, 255, 255, 0.06)';
                                                }
                                            }
                                        },
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                color: 'rgb(190, 190, 190)'
                                            },
                                            grid: {
                                                display: false
                                            }
                                        }
                                    },
                                    plugins: {
                                        legend: {
                                            display: false,
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    return context.dataset.data[context.dataIndex][2];
                                                },
                                                afterBody: function(context) {
                                                    return context[0].raw[0] + ' - ' + context[0].raw[1];
                                                }
                                            }
                                        }
                                    },

                                }
                            };

                            if (window.calendarChart !== null) {
                                window.calendarChart.destroy();
                            }
                            
                            window.calendarChart = new Chart(
                                content,
                                config
                            );

                            content.onclick = function(event) {
                                let points = window.calendarChart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true);
                                if (points.length) {
                                    const firstPoint = points[0];
                                    const label = window.calendarChart.data.labels[firstPoint.index];
                                    const value = window.calendarChart.data.datasets[firstPoint.datasetIndex].data[firstPoint.index];
                                    console.log(value);
                                    if (value.length) {
                                        document.getElementById('inpEditCalendarItemIdent').innerText = '#' + value[3] + ' ' + label;
                                        document.getElementById('inpEditCalendarItemId').value = value[3];
                                        document.getElementById('inpEditCalendarItemName').value = label;
                                        document.getElementById('inpEditCalendarItemDateFrom').value = value[0];
                                        document.getElementById('inpEditCalendarItemDateTill').value = value[1];
                                        document.getElementById('inpEditCalendarItemClass').value = value[4];
                                        window.vue.bShowEditCalendarItem = true;
                                    }
                                }
                            };
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            /**
             * Renders the full month-grid calendar (used on the /calendar page).
             * Reads/writes the currently displayed month from window.calendarViewYear
             * and window.calendarViewMonth (0-indexed).
             */
            renderCalendarMonth: function() {
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
                const firstWeekday = (firstOfMonth.getDay() + 6) % 7; // Monday = 0 .. Sunday = 6
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
                        headerCell.className = 'calendar-weekday-header' + ((i >= 5) ? ' is-weekend' : '');
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

                            if ((cellDateStr >= itemFrom) && (cellDateStr < itemTill)) {
                                let chip = document.createElement('div');
                                chip.className = 'calendar-event-chip';
                                chip.style.backgroundColor = item.color_background;
                                chip.style.borderColor = item.color_border;
                                chip.title = item.name + ' (' + item.class_name + ')';
                                chip.textContent = item.name;

                                chip.addEventListener('click', function(ev) {
                                    ev.stopPropagation();
                                    window.vue.editCalendarItemFromData(item);
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
            },

            shiftCalendarMonth: function(delta) {
                if (typeof window.calendarViewYear === 'undefined') {
                    let now = new Date();
                    window.calendarViewYear = now.getFullYear();
                    window.calendarViewMonth = now.getMonth();
                }

                let d = new Date(window.calendarViewYear, window.calendarViewMonth + delta, 1);
                window.calendarViewYear = d.getFullYear();
                window.calendarViewMonth = d.getMonth();

                window.vue.renderCalendarMonth();
            },

            goToCalendarToday: function() {
                let now = new Date();
                window.calendarViewYear = now.getFullYear();
                window.calendarViewMonth = now.getMonth();

                window.vue.renderCalendarMonth();
            },

            openAddCalendarItem: function(dateStr) {
                let dateFromInput = document.getElementById('add-calendar-item-date-from');
                let dateTillInput = document.getElementById('date-till');

                if (dateStr) {
                    if (dateFromInput) {
                        dateFromInput.value = dateStr;
                    }

                    if (dateTillInput) {
                        dateTillInput.value = dateStr;
                    }
                } else {
                    if (dateFromInput) {
                        dateFromInput.value = '';
                    }

                    if (dateTillInput) {
                        dateTillInput.value = '';
                    }
                }

                window.vue.bShowAddCalendarItem = true;
            },

            editCalendarItemFromData: function(item) {
                document.getElementById('inpEditCalendarItemIdent').innerText = '#' + item.id + ' ' + item.name;
                document.getElementById('inpEditCalendarItemId').value = item.id;
                document.getElementById('inpEditCalendarItemName').value = item.name;
                document.getElementById('inpEditCalendarItemDateFrom').value = item.date_from.split(' ')[0];
                document.getElementById('inpEditCalendarItemDateTill').value = item.date_till.split(' ')[0];
                document.getElementById('inpEditCalendarItemClass').value = item.class_descriptor;
                window.vue.bShowEditCalendarItem = true;
            },

            removeCalendarItem: function(ident) {
                window.vue.ajaxRequest('post', window.location.origin + '/calendar/remove', { ident: ident }, function(response) {
                    if (response.code == 200) {
                        location.reload();
                    } else {
                        alert(response.msg);
                    }
                });
            },

            removeCalendarClass: function(id) {
                window.vue.ajaxRequest('post', window.location.origin + '/admin/calendar/class/remove', { id: id }, function(response) {
                    if (response.code == 200) {
                        let elItem = document.getElementById('admin-calendar-class-item-' + id);
                        if (elItem) {
                            elItem.remove();
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            clonePlant: function(id) {
                window.vue.ajaxRequest('post', window.location.origin + '/plants/clone', { id: id }, function(response) {
                    if (response.code == 200) {
                        location.href = window.location.origin + '/plants/details/' + response.clone_id;
                    } else {
                        alert(response.msg);
                    }
                });
            },

            showPerformBulkUpdate: function(operation, title, button, location, is_custom = false, datatype = 'datetime') {
                document.getElementById('plant-bulk-perform-operation-operation').value = operation;
                document.getElementById('plant-bulk-perform-operation-location').value = location;
                document.getElementById('plant-bulk-perform-operation-title').innerText = title;
                document.getElementById('plant-bulk-perform-operation-button').innerText = button;
                document.getElementById('plant-bulk-perform-operation-custom').checked = is_custom;
                document.getElementById('plant-bulk-perform-operation-datatype').value = datatype;

                if (document.getElementById('plant-bulk-perform-operation-bulkvalue').tagName.toLowerCase() === 'select') {
                    let parentElem = document.getElementById('plant-bulk-perform-operation-bulkvalue').parentElement;
                    parentElem.innerHTML = `<input type="" class="input" name="bulkvalue" id="plant-bulk-perform-operation-bulkvalue" value="">`;
                }

                if (datatype === 'datetime') {
                    let curDate = new Date();
                    document.getElementById('plant-bulk-perform-operation-bulkvalue').type = 'date';
                    document.getElementById('plant-bulk-perform-operation-bulkvalue').value = curDate.toISOString().split('T')[0];
                } else if (datatype == 'string') {
                    document.getElementById('plant-bulk-perform-operation-bulkvalue').type = 'text';
                    document.getElementById('plant-bulk-perform-operation-bulkvalue').value = '';
                } else if (datatype == 'int') {
                    document.getElementById('plant-bulk-perform-operation-bulkvalue').type = 'number';
                    document.getElementById('plant-bulk-perform-operation-bulkvalue').value = 0;
                } else if (datatype == 'double') {
                    document.getElementById('plant-bulk-perform-operation-bulkvalue').type = 'text';
                    document.getElementById('plant-bulk-perform-operation-bulkvalue').value = '0.0';
                } else if (datatype == 'bool') {
                    document.getElementById('plant-bulk-perform-operation-bulkvalue').type = 'number';
                    document.getElementById('plant-bulk-perform-operation-bulkvalue').value = 0;
                } else {
                    document.getElementById('plant-bulk-perform-operation-bulkvalue').type = 'text';
                    document.getElementById('plant-bulk-perform-operation-bulkvalue').value = '';
                }
                
                window.vue.bulkChecked('plant-bulk-perform-operation', false);

                window.vue.bShowPlantBulkPerformUpdate = true;
            },

            bulkPerformPlantUpdate: function(target, attribute, location, bulkvalue, is_custom = false, bulktype = 'datetime') {
                let plantIds = [];

                let elems = document.getElementsByClassName(target);
                if (elems) {
                    Array.prototype.forEach.call(elems, function(elem) {
                        if (elem.checked) {
                            plantIds.push([elem.dataset.plantid, elem.dataset.plantname]);
                        }
                    });
                    
                    if (plantIds.length > 0) {
                        window.vue.ajaxRequest('post', window.location.origin + '/plants/update/bulk', { attribute: attribute, list: JSON.stringify(plantIds), location: location, bulkvalue: bulkvalue, bulktype: bulktype, custom: is_custom }, function(response) {
                            if (response.code == 200) {
                                alert(window.vue.operationSucceeded);
                                window.vue.bShowPlantBulkPerformUpdate = false;
                            } else {
                                alert(response.msg);
                            }
                        });
                    } else {
                    alert(window.vue.noListItemsSelected); 
                    }
                }
            },

            setBulkComboValues: function(list) {
                let parentElement = document.getElementById('plant-bulk-perform-operation-bulkvalue').parentElement;
                
                let listitems = '';

                list.forEach(function(elem, index) {
                    listitems += '<option value="' + elem.id + '">' + elem.name + '</option>';
                });

                parentElement.innerHTML = `<select class="input" name="bulkvalue" id="plant-bulk-perform-operation-bulkvalue">` + listitems + `</select>`;
            },

            generateAndShowQRCode: function(plant) {
                window.vue.ajaxRequest('get', window.location.origin + '/plants/qrcode?plant=' + plant, {}, function(response) {
                    if (response.code == 200) {
                        let elTarget = document.getElementById('image-plant-qr-code');
                        if (elTarget) {
                            elTarget.src = response.qrcode;
                            window.vue.bShowPlantQRCode = true;
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            printQRCode: function(content, title) {
                const html = '<html><head><title>' + title + '</title></head><body><img src="' + content + '"/></body></html>';

                const blob = new Blob([html], { type: 'text/html' });
                const url = URL.createObjectURL(blob);

                let wnd = window.open(url, title, 'height=auto, width=auto');

                wnd.onafterprint = function() {
                    wnd.close();
                    URL.revokeObjectURL(url);
                };

                wnd.onload = function() {
                    wnd.print();
                };
            },

            bulkChecked: function(target, flag) {
                let elems = document.getElementsByClassName(target);
                
                if (elems) {
                    Array.prototype.forEach.call(elems, function(elem){ 
                        elem.checked = flag; 
                    });
                }
            },

            bulkPrintQRCodes: function(target, location) {
                let plantIds = [];

                let elems = document.getElementsByClassName(target);
                if (elems) {
                    Array.prototype.forEach.call(elems, function(elem) {
                        if (elem.checked) {
                            plantIds.push([elem.dataset.plantid, elem.dataset.plantname]);
                        }
                    });

                    if (plantIds.length > 0) {
                        window.vue.ajaxRequest('post', window.location.origin + '/plants/qrcode/bulk', { list: JSON.stringify(plantIds) }, function(response) {
                            if (response.code == 200) {
                                let html = '<html><head><title>' + location + '</title></head><body>';

                                response.list.forEach(function(elem, index) {
                                    html += '<div style="position: relative; display: inline-block; margin-left: 10px; margin-right: 10px; margin-bottom: 10px;">#' + elem.plantid + ' ' + elem.plantname + '<br/><img src="' + elem.qrcode + '" width="152" height="152"/></div>';
                                });

                                html += '</body></html>';

                                const blob = new Blob([html], { type: 'text/html' });
                                const url = URL.createObjectURL(blob);

                                let wnd = window.open(url, location, 'height=auto, width=auto');

                                wnd.onafterprint = function() {
                                    wnd.close();
                                    URL.revokeObjectURL(url);
                                };
                                
                                wnd.onload = function() {
                                    wnd.print();
                                };
                            } else {
                                alert(response.msg);
                            }
                        });
                    } else {
                    alert(window.vue.noListItemsSelected); 
                    }
                }
            },

            queryInvQrCode: function(item) {
                window.vue.ajaxRequest('get', window.location.origin + '/inventory/qrcode?item=' + item, {}, function(response) {
                    if (response.code == 200) {
                        let elTarget = document.getElementById('image-inventory-qr-code');
                        if (elTarget) {
                            elTarget.src = response.qrcode;
                            window.vue.bShowInvItemQRCode = true;
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            bulkPrintInvQRCodes: function(target, title) {
                let invIds = [];

                let elems = document.getElementsByClassName(target);
                if (elems) {
                    Array.prototype.forEach.call(elems, function(elem) {
                        if (elem.checked) {
                            invIds.push([elem.dataset.invitemid, elem.dataset.invitemname, elem.dataset.invgroup]);
                        }
                    });

                    if (invIds.length > 0) {
                        window.vue.ajaxRequest('post', window.location.origin + '/inventory/qrcode/bulk', { list: JSON.stringify(invIds) }, function(response) {
                            if (response.code == 200) {
                                let html = '<html><head><title>' + title + '</title></head><body>';

                                response.list.forEach(function(elem, index) {
                                    html += '<div style="position: relative; display: inline-block; margin-left: 10px; margin-right: 10px; margin-bottom: 10px;">#' + elem.invitemid + ' [' + elem.invgroup + '] ' + elem.invitemname + '<br/><img src="' + elem.qrcode + '" width="152" height="152"/></div>';
                                });

                                html += '</body></html>';

                                const blob = new Blob([html], { type: 'text/html' });
                                const url = URL.createObjectURL(blob);

                                let wnd = window.open(url, title, 'height=auto, width=auto');

                                wnd.onafterprint = function() {
                                    wnd.close();
                                    URL.revokeObjectURL(url);
                                };

                                wnd.onload = function() {
                                    wnd.print();
                                };
                            } else {
                                alert(response.msg);
                            }
                        });
                    } else {
                    alert(window.vue.noListItemsSelected); 
                    }
                }
            },

            bulkExportInventory: function(target, format, title) {
                let invIds = [];

                let elems = document.getElementsByClassName(target);
                if (elems) {
                    Array.prototype.forEach.call(elems, function(elem) {
                        if (elem.checked) {
                            invIds.push([elem.dataset.invitemid, elem.dataset.invitemname, document.getElementById(elem.dataset.invdescription).innerText, elem.dataset.invgroup, elem.dataset.invamount, elem.dataset.invlocation, elem.dataset.invphoto, elem.dataset.invcreated, elem.dataset.invupdated]);
                        }
                    });

                    if (invIds.length > 0) {
                        window.vue.ajaxRequest('post', window.location.origin + '/inventory/export', { list: JSON.stringify(invIds), format: document.getElementById(format).value }, function(response) {
                            if (response.code == 200) {
                                const dlanchor = document.createElement('a');
                                dlanchor.href = response.resource;
                                dlanchor.target = '_blank';
                                dlanchor.setAttribute('download', response.resource);
                                dlanchor.click();
                            } else {
                                alert(response.msg);
                            }
                        });
                    } else {
                    alert(window.vue.noListItemsSelected); 
                    }
                }
            },

            editGalleryPhotoLabel: function(id, plant, old) {
                let newLabel = prompt(window.vue.editProperty, old);
                if (newLabel.length) {
                    window.vue.ajaxRequest('post', window.location.origin + '/plants/details/gallery/photo/label/edit', { id: id, label: newLabel, plant: plant }, function(response) {
                        if (response.code == 200) {
                            document.getElementById('photo-gallery-item-' + id).children[0].children[0].innerHTML = newLabel;
                        } else {
                            alert(response.msg);
                        }
                    });
                }
            },

            setGalleryPhotoAsMain: function(id, plant) {
                let query = confirm(window.vue.confirmSetGalleryPhotoAsMain);
                if (!query) {
                    return;
                }

                window.vue.ajaxRequest('post', window.location.origin + '/plants/details/gallery/photo/setmain', { id: id, plant: plant }, function(response) {
                    if (response.code == 200) {
                        location.href = window.location.origin + '/plants/details/' + plant;
                    } else {
                        alert(response.msg);
                    }
                });
            },

            removeSharedPhoto: function(ident) {
                window.vue.ajaxRequest('get', window.location.origin + '/share/photo/remove?ident=' + ident, {}, function(response) {
                    if (response.code == 200) {
                        let elem = document.getElementById('photo-share-entry-' + ident);
                        if (elem) {
                            elem.remove();
                        }
                    } else {
                        alert(response.msg);
                    }
                });
            },

            loadNextShareLogEntries: function(table, action) {
                window.vue.ajaxRequest('post', window.location.origin + '/profile/sharelog/fetch', { paginate: action.dataset.paginate }, function(response) {
                    if (response.code == 200) {
                        let tbody = table.getElementsByTagName('tbody')[0];

                        response.data.forEach(function(elem, index) {
                            let newRow = document.createElement('tr');
                            newRow.id = 'photo-share-entry-' + elem.id;
                            newRow.innerHTML = `
                                <td><a href="` + elem.url + `" target="_blank">` + elem.title + `</a></td>
                                <td title="`+ elem.created_at + `">` + elem.diffForHumans + `</td>
                                <td><a href="javascript:void(0);" onclick="if (confirm('` + window.vue.confirmRemoveSharedPlantPhoto + `')) { window.vue.removeSharedPhoto('` + elem.ident + `'); }"><i class="fas fa-trash-alt"></i></a></td>
                            `;

                            tbody.appendChild(newRow);
                        });

                        action.parentNode.parentNode.remove();

                        let actionRow = document.createElement('tr');
                        actionRow.id = 'share-log-load-more';
                        actionRow.classList.add('share-log-paginate');
                        actionRow.innerHTML = `<td colspan="3"><a href="javascript:void(0);" onclick="window.vue.loadNextShareLogEntries(document.getElementById('` + table.id + `'), this);" data-paginate="` + response.data[response.data.length - 1].id + `">` + window.vue.loadMore + `</a></td>`;
                        tbody.appendChild(actionRow);
                    } else {
                        alert(response.msg);
                    }
                });
            },

            acquireGeoPosition: function(destLatitude, destLongitude, button) {
                let oldText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp;' + button.innerHTML;

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        destLatitude.value = position.coords.latitude;
                        destLongitude.value = position.coords.longitude;

                        button.innerHTML = oldText;
                    });
                } else {
                    button.innerHTML = oldText;
                    
                    alert('Geolocation is not available');
                }
            },

            toggleApiKey: function(id) {
                window.vue.ajaxRequest('get', window.location.origin + '/admin/api/' + id + '/toggle', {}, function(response) {
                    if (response.code == 200) {
                        document.getElementById('api-key-checkbox-' + id).checked = response.active;
                    } else {
                        alert(response.msg);
                    }
                });
            },

            toggleAdminPlantAttribute: function(name) {
                window.vue.ajaxRequest('get', window.location.origin + '/admin/attribute/update?name=' + name, {}, function(response) {
                    if (response.code == 200) {
                        document.getElementById('admin-attributes-checkbox-' + name).checked = response.active;
                    } else {
                        alert(response.msg);
                    }
                });
            },

            toggleAdminBoolSetting: function(name) {
                window.vue.ajaxRequest('get', window.location.origin + '/admin/environment/boolean/toggle?name=' + name, {}, function(response) {
                    if (response.code == 200) {
                        document.getElementById('admin-attributes-checkbox-allow-custom-attributes').checked = response.value;
                    } else {
                        alert(response.msg);
                    }
                });
            },

            toggleAdminAuthInfoMessages: function(checked, warning, caution) {
                let elWarning = document.querySelector(warning);
                let elCaution = document.querySelector(caution);

                if (checked) {
                    if (elWarning) {
                        elWarning.style.display = 'block';
                    }

                    if (elCaution) {
                        elCaution.style.display = 'block';
                    }
                } else {
                    if (elWarning) {
                        elWarning.style.display = 'none';
                    }

                    if (elCaution) {
                        elCaution.style.display = 'none';
                    }
                }
            },

            performPlantRecognition: function(target, plantid) {
                const form = document.getElementById(target);
                const data = new FormData(form);

                window.vue.ajaxRequest('post', window.location.origin + '/plants/details/identify', data, function(response) {
                    if (response.code == 200) {
                        let dest = document.getElementById('recognized-plant-selection');

                        dest.innerHTML = '<fieldset>';

                        response.data.forEach(function(elem, index) {
                            dest.innerHTML += `
                                <div class="field">
                                    <div class="control">
                                        <input type="radio" name="plant-selection" data-plantid="` + plantid + `" data-plantname="` + elem.species.scientificNameWithoutAuthor + `" data-plantscientificname="` + elem.species.scientificName + `" onclick="document.getElementById('action-save-selected-plant-data').disabled = !window.vue.allRecognizedPlantSelectionGroupsValid();">&nbsp;<a class="is-default-link" href="` + window.vue.plantSearchURL(elem.species.scientificNameWithoutAuthor) + `">` + elem.species.scientificNameWithoutAuthor + `</a> (` + (elem.score * 100).toFixed(2) + '%)' + `
                                    </div>
                                </div>
                                `;
                        });

                        dest.innerHTML += '</fieldset>';

                        document.getElementById('plant-rec-action-icon').classList.remove('fa-spinner');
                        document.getElementById('plant-rec-action-icon').classList.remove('fa-spin');
                        document.getElementById('plant-rec-action-icon').classList.add('fa-microscope');

                        window.vue.bShowSelectRecognizedPlant = true;
                    } else {
                        alert(response.msg);
                    }
                });
            },

            storeRecognizedPlantData: function(target, update_name, update_scientific_name) {
                const selection = document.getElementById(target).getElementsByTagName('input');

                for (let i = 0; i < selection.length; i++) {
                    if (selection[i].checked) {
                        const item = selection[i];

                        window.plantRecStorageStep = 0;
                        window.plantRecErrorCount = 0;

                        if (update_name) {
                            window.vue.ajaxRequest('post', window.location.origin + '/plants/details/edit/ajax', {
                                plant: item.dataset.plantid,
                                attribute: 'name',
                                value: item.dataset.plantname
                            }, function(response) {
                                window.plantRecStorageStep++;
                                
                                if (response.code == 500) {
                                    window.plantRecErrorCount++;
                                    alert(response.msg);
                                }
                            });
                        } else {
                            window.plantRecStorageStep++;
                        }

                        if (update_scientific_name) {
                            window.vue.ajaxRequest('post', window.location.origin + '/plants/details/edit/ajax', {
                                plant: item.dataset.plantid,
                                attribute: 'scientific_name',
                                value: item.dataset.plantscientificname
                            }, function(response) {
                                window.plantRecStorageStep++;

                                if (response.code == 500) {
                                    window.plantRecErrorCount++;
                                    alert(response.msg);
                                }
                            });
                        } else {
                            window.plantRecStorageStep++;
                        }

                        setTimeout(function awaitPlantAttributeStorage() {
                            if (window.plantRecStorageStep < 2) {
                                setTimeout(awaitPlantAttributeStorage, 1000);
                            } else {
                                location.reload();
                            }
                        }, 100);

                        break;
                    }
                }
            },

            recognizedPlantsGroupSelectionValid: function(group, type) {
                let elems = document.getElementById(group).querySelectorAll('input[type="' + type + '"]');

                for (let i = 0; i < elems.length; i++) {
                    if (elems[i].checked) {
                        return true;
                    }
                }

                return false;
            },

            allRecognizedPlantSelectionGroupsValid: function() {
                return (window.vue.recognizedPlantsGroupSelectionValid('recognized-plant-selection', 'radio')) && (window.vue.recognizedPlantsGroupSelectionValid('plants-attribute-selection', 'checkbox'));
            },

            quickPlantRecognition: function(target, actionIcon, destContent) {
                const form = document.getElementById(target);
                const data = new FormData(form);

                window.vue.ajaxRequest('post', window.location.origin + '/plants/details/identify', data, function(response) {
                    if (response.code == 200) {
                        let dest = document.getElementById(destContent);

                        dest.innerHTML = '<fieldset>';

                        response.data.forEach(function(elem, index) {
                            dest.innerHTML += `
                                <div class="field">
                                    <div class="control">
                                        <div><a class="is-default-link" href="` + window.vue.plantSearchURL(elem.species.scientificNameWithoutAuthor) + `">` + elem.species.scientificNameWithoutAuthor + `</a> (` + (elem.score * 100).toFixed(2) + '%)' + `</div>
                                    </div>
                                </div>
                                `;
                        });

                        dest.innerHTML += '</fieldset>';

                        document.getElementById(actionIcon).classList.remove('fa-spinner');
                        document.getElementById(actionIcon).classList.remove('fa-spin');
                        document.getElementById(actionIcon).classList.add('fa-microscope');

                        window.vue.bShowQuickScanPlant = true;
                    } else {
                        alert(response.msg);
                    }
                });
            },

            saveLocationNotes: function(location, notes, reselem) {
                let elem = document.getElementById(notes);
                if (elem) {
                    let elNotes = document.getElementById(notes);

                    window.vue.ajaxRequest('post', window.location.origin + '/plants/location/' + location + '/notes/save', { notes: elNotes.value }, function(response) {
                        if (response.code == 200) {
                            let elResult = document.getElementById(reselem);
                            if (elResult) {
                                elResult.innerHTML = '<i class="far fa-check-circle fa-lg"></i>';
                            }
                        } else {
                            alert(response.msg);
                        }
                    });
                }
            },

            setElementGroupStatus: function(container, tagname, type, flag) {
                let subelems = document.querySelector(container).getElementsByTagName(tagname);
                
                for (let i = 0; i < subelems.length; i++) {
                    if (subelems[i].type === type) {
                        subelems[i].disabled = flag;
                    }
                }
            },

            validateAndSubmitForm: function(form, button) {
                let origtext = button.innerHTML;
                button.innerHTML = '<i class=\'fas fa-spinner fa-spin\'></i>&nbsp;' + window.vue.loading_please_wait; 

                if (form.checkValidity()) {
                    form.submit();
                } else {
                    form.reportValidity();
                    button.innerHTML = origtext;
                }
            },

            fixQuickScanPos: function(pwa = false) {
                let quickscanwidget = document.querySelector('.quickscan');
                if (quickscanwidget) {
                    quickscanwidget.style.bottom = '12px';

                    if ((pwa) && (window.innerWidth <= 1089)) {
                        quickscanwidget.style.bottom = '83px';
                    }
                }
            },

            clearCache: function(button) {
                window.vue.ajaxRequest('post', window.location.origin + '/admin/cache/clear', {}, function(response) {
                    if (response.code == 200) {
                        button.innerHTML = '<i class="fas fa-check"></i>&nbsp;' + button.innerHTML;
                        button.setAttribute('disabled', 'disabled');
                    } else {
                        alert(response.msg);
                    }
                });
            },

            sendTestMail: function(button) {
                if (window.vue.origTestMailButtonContent === '') {
                    window.vue.origTestMailButtonContent = button.innerHTML;
                }

                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp;' + window.vue.origTestMailButtonContent;

                window.vue.ajaxRequest('post', window.location.origin + '/admin/mail/test', {}, function(response) {
                    if (response.code == 200) {
                        button.innerHTML = '<i class="fas fa-check"></i>&nbsp;' + window.vue.origTestMailButtonContent;
                    } else {
                        alert(response.msg);
                    }
                });
            },

            scrollTo: function(target) {
                let elem = document.querySelector(target);
                if (elem) {
                    elem.scrollIntoView({ behavior: 'smooth' });
                }
            },

            copyToClipboard: function(text) {
                const el = document.createElement('textarea');
                el.value = text;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                alert(window.vue.copiedToClipboard);
            },

            playAudio: function(soundfile) {
                let audio = new Audio(window.location.origin + '/snd/' + soundfile);
                audio.onloadeddata = function() {
                    audio.play();
                };
            },

            plantSearchURL: function(plantname) {
                return 'https://www.ecosia.org/images?q=' + plantname.replaceAll(' ', '+').toLowerCase();
            },

            isProgressiveWebApp: function() {
                return window.matchMedia('(display-mode: standalone)').matches;
            },
        }
    });
};

document.addEventListener('DOMContentLoaded', function() {
    window.vue = window.createVueInstance('#app');
});