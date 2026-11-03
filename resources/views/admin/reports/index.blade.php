@include('admin.reports.partials.workbook')

    <div class="modal fade" id="pdfGenerationModal" tabindex="-1" aria-hidden="true" aria-labelledby="pdfGenerationTitle" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content pdf-generation-modal border-0">
                <div class="modal-body text-center">
                    <div id="pdfGenerationSpinner" class="pdf-generation-spinner" aria-hidden="true"></div>
                    <div id="pdfGenerationErrorIcon" class="pdf-generation-error" hidden aria-hidden="true"><i data-lucide="file-warning"></i></div>
                    <span class="pdf-generation-eyebrow">Report export</span>
                    <h5 id="pdfGenerationTitle">Generating PDF report…</h5>
                    <p id="pdfGenerationMessage">Preparing the selected records and formatting your document.</p>
                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <button type="button" id="cancelPdfGeneration" class="btn btn-light border rounded-pill px-4">Cancel</button>
                        <button type="button" id="retryPdfGeneration" class="btn btn-htc rounded-pill px-4" hidden>Try again</button>
                    </div>
                </div>
            </div>
        </div>
    </div>



@include('admin.reports.partials.workbook-styles')

<script>
    const initializeReportBuilder = () => {
        const tabStrip = document.getElementById('reportTabs');
        const selectedTab = document.getElementById('selectedReportTab');
        if (tabStrip && selectedTab) {
            tabStrip.scrollLeft = Math.max(0, selectedTab.offsetLeft - tabStrip.offsetLeft - (tabStrip.clientWidth - selectedTab.offsetWidth) / 2);
        }
        const reportForm = document.getElementById('reportForm');
        const pdfButton = document.getElementById('downloadPdfButton');
        const pdfModalElement = document.getElementById('pdfGenerationModal');
        const cancelPdfButton = document.getElementById('cancelPdfGeneration');
        const retryPdfButton = document.getElementById('retryPdfGeneration');
        const pdfTitle = document.getElementById('pdfGenerationTitle');
        const pdfMessage = document.getElementById('pdfGenerationMessage');
        const pdfSpinner = document.getElementById('pdfGenerationSpinner');
        const pdfErrorIcon = document.getElementById('pdfGenerationErrorIcon');
        let pdfRequest = null;
        let cancelPending = false;

        if (!reportForm || !pdfButton || !pdfModalElement || !window.bootstrap?.Modal) return;

        document.body.appendChild(pdfModalElement);
        const pdfModal = bootstrap.Modal.getOrCreateInstance(pdfModalElement);

        const setPdfState = (state, message = '') => {
            const failed = state === 'error';
            pdfTitle.textContent = failed ? 'PDF generation failed' : 'Generating PDF report…';
            pdfMessage.textContent = failed
                ? (message || 'The report could not be generated. Check your connection and try again.')
                : 'Preparing the selected records and formatting your document.';
            pdfSpinner.hidden = failed;
            pdfErrorIcon.hidden = !failed;
            retryPdfButton.hidden = !failed;
            cancelPdfButton.textContent = failed ? 'Close' : 'Cancel';
            window.lucide?.createIcons();
        };

        const closePdfModal = () => {
            pdfModal.hide();
            pdfButton.disabled = false;
        };

        pdfModalElement.addEventListener('shown.bs.modal', () => {
            // Bootstrap ignores hide() calls made while a modal is still in its
            // opening transition. Retry as soon as that transition completes.
            if (cancelPending) closePdfModal();
        });

        pdfModalElement.addEventListener('hidden.bs.modal', () => {
            cancelPending = false;
            pdfButton.disabled = false;
        });

        const filenameFrom = (response) => {
            const disposition = response.headers.get('Content-Disposition') || '';
            const encoded = disposition.match(/filename\*=UTF-8''([^;]+)/i)?.[1];
            const plain = disposition.match(/filename="?([^";]+)"?/i)?.[1];
            return decodeURIComponent(encoded || plain || `HTC_SMO_Report_${Date.now()}.pdf`);
        };

        const generatePdf = async () => {
            if (pdfRequest) return;
            if (!window.htcFilters?.validate(reportForm, reportForm.closest('[data-filter-workspace]'))) return;

            cancelPending = false;
            setPdfState('loading');
            pdfButton.disabled = true;
            const controller = new AbortController();
            pdfRequest = controller;
            pdfModal.show();

            const query = new URLSearchParams(new FormData(reportForm));
            query.delete('export');
            query.delete('print');
            query.set('download_pdf', '1');
            const url = new URL(reportForm.action, window.location.href);
            url.search = query.toString();

            try {
                const response = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {Accept: 'application/pdf', 'X-Requested-With': 'XMLHttpRequest'},
                    signal: controller.signal,
                });
                if (!response.ok) {
                    const error = new Error('The report could not be generated. Check your connection and try again.');
                    if (response.status === 422 && (response.headers.get('Content-Type') || '').includes('application/json')) {
                        const details = await response.json();
                        error.message = details.message || error.message;
                    }
                    throw error;
                }
                if (!(response.headers.get('Content-Type') || '').toLowerCase().includes('application/pdf')) {
                    throw new Error('The server did not return a PDF document.');
                }

                const filename = filenameFrom(response);
                const blob = await response.blob();
                if (window.htcSession?.expired) {
                    closePdfModal();
                    return;
                }
                if (controller.signal.aborted) return;

                const objectUrl = URL.createObjectURL(blob);
                const download = document.createElement('a');
                download.href = objectUrl;
                download.download = filename;
                document.body.appendChild(download);

                closePdfModal();
                download.click();
                download.remove();
                window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
            } catch (error) {
                if (error.name === 'SessionExpiredError') {
                    closePdfModal();
                    return;
                }
                if (error.name === 'AbortError') {
                    closePdfModal();
                } else {
                    setPdfState('error', error.message);
                }
            } finally {
                if (pdfRequest === controller) pdfRequest = null;
                pdfButton.disabled = false;
            }
        };

        pdfButton.addEventListener('click', generatePdf);
        retryPdfButton.addEventListener('click', () => {
            pdfRequest = null;
            generatePdf();
        });
        cancelPdfButton.addEventListener('click', () => {
            cancelPending = true;
            pdfRequest?.abort();
            pdfButton.disabled = false;
            closePdfModal();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeReportBuilder, {once: true});
    } else {
        initializeReportBuilder();
    }
</script>
