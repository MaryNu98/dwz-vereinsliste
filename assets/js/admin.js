(function() {
    function $(selector, context) {
        return (context || document).querySelector(selector);
    }

    function createFormData(data) {
        var formData = new FormData();
        Object.keys(data).forEach(function(key) {
            formData.append(key, data[key]);
        });
        return formData;
    }

    function updateProgress(progress) {
        var progressFill = $('#dwz-vl-fide-progress-fill');
        var progressText = $('#dwz-vl-fide-progress-text');
        var progressContainer = $('#dwz-vl-fide-progress-container');

        if (!progressContainer || !progressFill || !progressText) {
            return;
        }

        progressContainer.style.display = 'block';
        progressFill.style.width = progress.percent + '%';
        progressText.textContent = progress.message + ' (' + progress.percent + '%)';
    }

    function setButtonState(button, enabled) {
        button.disabled = !enabled;
        if (enabled) {
            button.textContent = dwzVereinListAdmin.labels.startUpdate;
        } else {
            button.textContent = dwzVereinListAdmin.labels.updateRunning;
        }
    }

    function formatDateIso(dateString) {
        if (!dateString) {
            return dwzVereinListAdmin.labels.never;
        }

        try {
            var date = new Date(dateString + ' UTC');
            return date.toLocaleString();
        } catch (e) {
            return dateString;
        }
    }

    function pollProgress() {
        var button = $('#dwz-vl-fide-update-button');
        var lastUpdated = $('#dwz-vl-fide-last-updated');
        var progressUrl = dwzVereinListAdmin.ajaxUrl;

        fetch(progressUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: createFormData({
                action: 'dwz_vl_fide_update_progress',
                nonce: dwzVereinListAdmin.nonce,
            }),
        })
            .then(function(response) {
                return response.json();
            })
            .then(function(response) {
                if (!response.success) {
                    updateProgress({ percent: 0, message: dwzVereinListAdmin.labels.error });
                    setButtonState(button, true);
                    return;
                }

                var data = response.data;
                updateProgress(data);

                if ( data.status === 'completed' ) {
                    setButtonState(button, true);
                    if ( lastUpdated ) {
                        lastUpdated.textContent = formatDateIso(data.last_update || dwzVereinListAdmin.labels.never);
                    }
                    return;
                }

                if ( data.status === 'error' ) {
                    setButtonState(button, true);
                    return;
                }

                setTimeout(pollProgress, 1500);
            })
            .catch(function() {
                updateProgress({ percent: 0, message: dwzVereinListAdmin.labels.error });
                setButtonState(button, true);
            });
    }

    function init() {
        var button = $('#dwz-vl-fide-update-button');
        if (!button) {
            return;
        }

        setButtonState(button, true);

        button.addEventListener('click', function() {
            setButtonState(button, false);
            updateProgress({ percent: 0, message: dwzVereinListAdmin.labels.starting });

            fetch(dwzVereinListAdmin.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: createFormData({
                    action: 'dwz_vl_manual_fide_update',
                    nonce: dwzVereinListAdmin.nonce,
                }),
            })
                .then(function(response) {
                    return response.json();
                })
                .then(function(response) {
                    if (!response.success) {
                        updateProgress({ percent: 0, message: response.data ? response.data.message : dwzVereinListAdmin.labels.error });
                        setButtonState(button, true);
                        return;
                    }

                    pollProgress();
                })
                .catch(function() {
                    updateProgress({ percent: 0, message: dwzVereinListAdmin.labels.error });
                    setButtonState(button, true);
                });
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();