<x-layouts::app title="Envelopes">
    <style>
        :root {
            --ink: #131b24;
            --muted: #6f7782;
            --line: #d9dde3;
            --paper: #f3f6fb;
            --card: #ffffff;
            --brand: #0f766e;
            --brand-strong: #0c5f59;
            --chip: #d7f3ef;
            --danger: #b42318;
            --glow-a: #9fe8d9;
            --glow-b: #ffd3a9;
        }

        .shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 28px 18px 36px;
        }

        .hero {
            border: 1px solid #c6d7d4;
            border-radius: 20px;
            padding: 22px;
            background: linear-gradient(138deg, #f8fffd, #f6fbff 42%, #fff8f1);
            box-shadow: 0 20px 40px rgba(17, 24, 39, 0.08);
            transform: translateY(8px);
            opacity: 0;
            animation: hero-in 430ms ease forwards;
        }

        @keyframes hero-in {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .hero-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 600;
            background: var(--chip);
            color: #04534e;
            border: 1px solid #8acfc4;
            border-radius: 999px;
            padding: 6px 12px;
        }

        .badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--brand);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.13);
        }

        h1 {
            margin: 14px 0 4px;
            font-size: clamp(28px, 4.3vw, 38px);
            line-height: 1.1;
            letter-spacing: -0.03em;
        }

        .sub {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .btn {
            border: 1px solid transparent;
            border-radius: 11px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform .15s ease, filter .15s ease, background-color .15s ease;
        }

        .btn:disabled {
            opacity: .5;
            cursor: not-allowed;
            background-color: var(--line) !important;
        }

        .btn-primary:disabled {
            background-color: var(--muted) !important;
        }

        input:disabled,
        select:disabled {
            background-color: #f5f7fa;
            color: var(--muted);
            cursor: not-allowed;
        }

        .btn-primary {
            color: #fff;
            background: var(--brand);
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--brand-strong);
        }

        .btn-soft {
            color: #26404c;
            background: #fff;
            border-color: var(--line);
        }

        .btn-soft:hover:not(:disabled) {
            filter: brightness(0.98);
        }

        .btn:active:not(:disabled) {
            transform: translateY(1px);
        }

        .panel {
            margin-top: 14px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 14px 28px rgba(17, 24, 39, 0.06);
            overflow: hidden;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(to bottom, #ffffff, #fbfcfe);
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field label {
            font-size: 12px;
            font-weight: 600;
            color: #445364;
            letter-spacing: .01em;
        }

        .field input,
        .field select {
            width: 100%;
            border: 1px solid #cfd6de;
            border-radius: 10px;
            background: #fff;
            padding: 10px 12px;
            font-size: 14px;
            min-height: 42px;
        }

        .field input:focus,
        .field select:focus {
            border-color: #2d8f87;
            outline: 3px solid rgba(45, 143, 135, 0.16);
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th,
        td {
            padding: 12px 12px;
            border-bottom: 1px solid #ecf0f4;
            vertical-align: middle;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f8fafc;
            font-size: 12px;
            color: #4d5968;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        tr {
            opacity: 0;
            transform: translateY(3px);
            animation: row-in 240ms ease forwards;
        }

        @keyframes row-in {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .status {
            display: inline-flex;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid;
            text-transform: capitalize;
        }

        .status-sent,
        .status-completed,
        .status-signed {
            color: #0f5f38;
            background: #e9faee;
            border-color: #9fddaf;
        }

        .status-draft,
        .status-created,
        .status-awaiting {
            color: #6a4c06;
            background: #fff6dd;
            border-color: #e7ca78;
        }

        .status-cancelled,
        .status-expired,
        .status-declined,
        .status-voided {
            color: #8e1e2f;
            background: #ffebee;
            border-color: #e8a7b1;
        }

        .status-default {
            color: #2f3f4f;
            background: #ecf2fa;
            border-color: #bfd0e5;
        }

        .row-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .empty,
        .error {
            padding: 28px 16px;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
        }

        .error {
            color: var(--danger);
        }

        .foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 14px 16px;
        }

        .inline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .hint {
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 920px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .hero {
                padding: 18px;
                border-radius: 16px;
            }

            .panel {
                border-radius: 14px;
            }
        }
    </style>

<div class="shell">
    <section class="hero">
        <div class="hero-top">
            <span class="badge"><span class="badge-dot"></span> Connected to /api/signable/envelopes</span>
            <a href="{{ route('envelopes.send') }}" class="btn btn-soft" style="text-decoration: none;">Go to Send Envelope</a>
        </div>
        <h1>Envelope Desk</h1>
        <p class="sub">Browse envelopes, filter quickly, download one, or batch download selected envelopes.</p>
        <div class="actions">
            <button id="refreshBtn" class="btn btn-soft" type="button">Refresh List</button>
            <button id="exportBtn" class="btn btn-soft" type="button">Export to Excel</button>
            <button id="batchBtn" class="btn btn-primary" type="button" disabled>Batch Download Selected</button>
            <span id="liveStatus" class="hint" aria-live="polite"></span>
        </div>
    </section>

    <section class="panel">
        <div class="filter-grid">
            <div class="field">
                <label for="searchInput">Search</label>
                <input id="searchInput" type="text" placeholder="title, email, fingerprint..." />
            </div>
            <div class="field">
                <label for="statusInput">Status</label>
                <select id="statusInput">
                    <option value="">All statuses</option>
                    <option value="sent">Sent</option>
                    <option value="signed">Signed</option>
                    <option value="draft">Draft</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="expired">Expired</option>
                    <option value="rejected">Rejected</option>
                    <option value="processing">Processing</option>
                    <option value="failed">Failed</option>
                    <option value="verify">Verify</option>
                </select>
            </div>
            <div class="field">
                <label for="limitInput">Rows</label>
                <select id="limitInput">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="field">
                <label for="dateFromInput">Created From</label>
                <input id="dateFromInput" type="date" />
            </div>
            <div class="field">
                <label for="dateToInput">Created To</label>
                <input id="dateToInput" type="date" />
            </div>
            <div class="field" style="justify-content:flex-end;">
                <label>&nbsp;</label>
                <button id="applyBtn" class="btn btn-primary" type="button">Apply Filter</button>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th><input id="selectAll" type="checkbox" aria-label="Select all" /></th>
                    <th>Title</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Fingerprint</th>
                    <th>Download</th>
                </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>

        <div id="emptyState" class="empty" style="display:none;">No envelopes matched the current filters.</div>
        <div id="errorState" class="error" style="display:none;"></div>

        <div class="foot">
            <div class="inline hint">
                <span id="pagingMeta">Showing 0 envelopes</span>
            </div>
            <div class="inline">
                <button id="prevBtn" class="btn btn-soft" type="button">Previous</button>
                <button id="nextBtn" class="btn btn-soft" type="button">Next</button>
            </div>
        </div>
    </section>
</div>

<script>
(() => {
    const state = {
        offset: 0,
        limit: 25,
        search: '',
        status: '',
        dateFrom: '',
        dateTo: '',
        loading: false,
        envelopes: [],
        totalMatched: 0,
        allFilteredEnvelopes: [], // Cache of all envelopes matching current filters
        usingClientFilteredPaging: false,
        selected: new Set()
    };

    const rowsEl = document.getElementById('rows');
    const emptyEl = document.getElementById('emptyState');
    const errorEl = document.getElementById('errorState');
    const statusEl = document.getElementById('liveStatus');
    const pagingEl = document.getElementById('pagingMeta');
    const batchBtn = document.getElementById('batchBtn');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const selectAll = document.getElementById('selectAll');

    const searchInput = document.getElementById('searchInput');
    const statusInput = document.getElementById('statusInput');
    const limitInput = document.getElementById('limitInput');
    const dateFromInput = document.getElementById('dateFromInput');
    const dateToInput = document.getElementById('dateToInput');

    document.getElementById('refreshBtn').addEventListener('click', () => loadEnvelopes());
    document.getElementById('exportBtn').addEventListener('click', () => exportToExcel());
    document.getElementById('applyBtn').addEventListener('click', () => {
        state.search = searchInput.value.trim();
        state.status = statusInput.value; // This will be passed to API as query param
        state.limit = Number(limitInput.value) || 25;
        state.dateFrom = dateFromInput.value; // Client-side filter for processed date
        state.dateTo = dateToInput.value; // Client-side filter for processed date
        state.offset = 0;
        state.selected.clear();
        state.allFilteredEnvelopes = [];
        loadEnvelopes();
    });

    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('applyBtn').click();
        }
    });

    prevBtn.addEventListener('click', () => {
        if (state.offset === 0 || state.loading) {
            return;
        }
        state.offset = Math.max(0, state.offset - state.limit);
        loadEnvelopes();
    });

    nextBtn.addEventListener('click', () => {
        if (state.loading) {
            return;
        }
        state.offset += state.limit;
        loadEnvelopes();
    });

    selectAll.addEventListener('change', () => {
        state.envelopes.forEach((env) => {
            const fp = getEnvelopeFingerprint(env);
            if (!fp) {
                return;
            }
            if (selectAll.checked) {
                state.selected.add(fp);
            } else {
                state.selected.delete(fp);
            }
        });
        renderRows();
        syncBatchButton();
    });

    batchBtn.addEventListener('click', async () => {
        const selectedIds = [...state.selected];
        if (!selectedIds.length || state.loading) {
            return;
        }

        batchBtn.disabled = true;
        setLiveStatus('Building ZIP from selected envelopes...');

        try {
            const response = await fetch('/api/signable/envelopes/batch-download', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/zip, application/json'
                },
                body: JSON.stringify({ fingerprints: selectedIds })
            });

            if (!response.ok) {
                let message = 'Failed to build batch ZIP.';
                try {
                    const json = await response.json();
                    if (json?.message) {
                        message = json.message;
                    }
                } catch {
                    // Ignore JSON parse failure when response is not JSON.
                }

                setLiveStatus(message);
                return;
            }

            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            const disposition = response.headers.get('Content-Disposition') || '';
            const matchedName = disposition.match(/filename="?([^\";]+)"?/i);
            anchor.href = objectUrl;
            anchor.download = matchedName ? matchedName[1] : 'envelopes.zip';
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
            URL.revokeObjectURL(objectUrl);
            setLiveStatus('Batch ZIP download started.');
        } catch {
            setLiveStatus('Failed to build batch ZIP.');
        } finally {
            syncBatchButton();
        }
    });

    async function exportToExcel() {
        const exportBtn = document.getElementById('exportBtn');
        exportBtn.disabled = true;
        setLiveStatus('Preparing export...');

        const allEnvelopes = [];
        const exportPageSize = 50;
        const maxParallelRequests = 3;

        try {
            // First, fetch the initial page to understand data size
            const params = new URLSearchParams();
            params.set('offset', '0');
            params.set('limit', String(exportPageSize));
            if (state.search) {
                params.set('q', state.search);
            }

            let response = await fetch('/api/signable/envelopes?' + params.toString(), {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status + ' while fetching envelopes.');
            }

            let payload = await response.json();
            let rawPage = normalizeEnvelopeList(payload);
            
            let page = filterEnvelopesByProcessedDate(rawPage, state.dateFrom, state.dateTo);

            allEnvelopes.push(...page);
            setLiveStatus('Fetching envelopes… (1 of ~' + Math.ceil((allEnvelopes.length + 100) / exportPageSize) + ' pages)');

            // If we got fewer than page size, we're done
            if (rawPage.length < exportPageSize) {
                // Process and export
                await processAndExportData(allEnvelopes);
                return;
            }

            // Fetch remaining pages in parallel batches
            let currentOffset = exportPageSize;
            let pageNumber = 1;

            while (true) {
                const batchPromises = [];
                const batchOffsets = [];

                // Create parallel fetch tasks
                for (let i = 0; i < maxParallelRequests; i++) {
                    const offset = currentOffset + (i * exportPageSize);
                    batchOffsets.push(offset);

                    const fetchPromise = (function(off) {
                        const p = new URLSearchParams();
                        p.set('offset', String(off));
                        p.set('limit', String(exportPageSize));
                        
                        // Add status filter to API
                        if (state.status) {
                            p.set('status', state.status);
                        }
                        
                        if (state.search) {
                            p.set('q', state.search);
                        }

                        return fetch('/api/signable/envelopes?' + p.toString(), {
                            headers: { 'Accept': 'application/json' }
                        })
                        .then(res => {
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            return res.json();
                        });
                    })(offset);

                    batchPromises.push(fetchPromise);
                }

                const batchResults = await Promise.all(batchPromises);
                let hasMoreData = false;

                for (const result of batchResults) {
                    const raw = normalizeEnvelopeList(result);
                    const filtered = filterEnvelopesByProcessedDate(raw, state.dateFrom, state.dateTo);
                    
                    allEnvelopes.push(...filtered);

                    if (raw.length >= exportPageSize) {
                        hasMoreData = true;
                    }

                    pageNumber++;
                }

                setLiveStatus('Fetching envelopes… (' + allEnvelopes.length + ' collected)');

                if (!hasMoreData) {
                    break;
                }

                currentOffset += exportPageSize * maxParallelRequests;
            }

            await processAndExportData(allEnvelopes);
        } catch (error) {
            setLiveStatus('Export failed: ' + (error?.message || 'Unknown error.'));
        } finally {
            exportBtn.disabled = false;
        }
    }

    async function processAndExportData(allEnvelopes) {
        if (allEnvelopes.length === 0) {
            setLiveStatus('No envelopes to export.');
            return;
        }

        setLiveStatus('Processing ' + allEnvelopes.length + ' envelope(s)…');

        const headers = ['Title', 'Contact', 'Status', 'Created', 'Fingerprint'];
        const csvRows = allEnvelopes.map((env) => [
            getEnvelopeTitle(env),
            getEnvelopeContactName(env),
            getEnvelopeStatus(env),
            getEnvelopeCreatedAt(env),
            getEnvelopeFingerprint(env)
        ]);

        const csvContent = [headers, ...csvRows]
            .map((row) => row.map((cell) => '"' + String(cell ?? '').replace(/"/g, '""') + '"').join(','))
            .join('\r\n');

        const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const objectUrl = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        const dateStamp = new Date().toISOString().slice(0, 10);
        anchor.href = objectUrl;
        anchor.download = 'envelopes_' + dateStamp + '.csv';
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
        URL.revokeObjectURL(objectUrl);

        setLiveStatus('Exported ' + allEnvelopes.length + ' envelope(s) to CSV.');
    }

    async function loadEnvelopes() {
        state.loading = true;
        updateLoadingUI(true);
        setLiveStatus('Loading envelopes...');
        errorEl.style.display = 'none';
        emptyEl.style.display = 'none';
        rowsEl.innerHTML = '<tr><td colspan="7" style="padding: 40px; text-align: center; color: var(--muted);"><strong>Loading envelopes...</strong><br><small style="font-size: 12px;">This may take a moment if applying filters to a large dataset.</small></td></tr>';

        try {
            // Check if we need client-side filtering for date range on envelope_processed
            const hasDateFilters = Boolean(state.dateFrom || state.dateTo);

            if (hasDateFilters) {
                // For date filtering, we need to fetch all matching status and filter by date client-side
                if (state.offset === 0 || state.allFilteredEnvelopes.length === 0) {
                    setLiveStatus('Filtering envelopes by processed date...');
                    state.allFilteredEnvelopes = await fetchFilteredEnvelopePool();
                }

                state.totalMatched = state.allFilteredEnvelopes.length;

                // Validate offset is within bounds
                if (state.offset >= state.totalMatched && state.totalMatched > 0) {
                    state.offset = Math.floor((state.totalMatched - 1) / state.limit) * state.limit;
                }

                // Extract current page from filtered pool
                state.envelopes = state.allFilteredEnvelopes.slice(state.offset, state.offset + state.limit);
                state.usingClientFilteredPaging = true;
            } else {
                // Server-side pagination: use API status filter + search
                const params = new URLSearchParams();
                params.set('offset', String(state.offset));
                params.set('limit', String(state.limit));
                
                // Add status filter to API request
                if (state.status) {
                    params.set('status', state.status);
                }
                
                // Add search query if provided
                if (state.search) {
                    params.set('q', state.search);
                }

                const response = await fetch('/api/signable/envelopes?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ' while loading envelopes.');
                }

                const payload = await response.json();
                state.envelopes = normalizeEnvelopeList(payload);
                state.totalMatched = state.envelopes.length;
                state.usingClientFilteredPaging = false;
                state.allFilteredEnvelopes = [];
            }

            // Handle edge case: offset went beyond results
            if (state.offset > 0 && state.envelopes.length === 0) {
                state.offset = Math.max(0, state.offset - state.limit);
                await loadEnvelopes();
                return;
            }

            renderRows();
            syncPagination();
            setLiveStatus('Loaded ' + state.envelopes.length + ' envelope(s).');
        } catch (error) {
            rowsEl.innerHTML = '';
            emptyEl.style.display = 'none';
            errorEl.style.display = 'block';
            errorEl.textContent = error?.message || 'Failed to load envelopes.';
            setLiveStatus('Failed to load envelopes.');
        } finally {
            state.loading = false;
            updateLoadingUI(false);
        }
    }

    function updateLoadingUI(isLoading) {
        // Disable all filter controls while loading
        searchInput.disabled = isLoading;
        statusInput.disabled = isLoading;
        limitInput.disabled = isLoading;
        dateFromInput.disabled = isLoading;
        dateToInput.disabled = isLoading;
        
        const applyBtn = document.getElementById('applyBtn');
        const refreshBtn = document.getElementById('refreshBtn');
        const exportBtn = document.getElementById('exportBtn');
        
        applyBtn.disabled = isLoading;
        refreshBtn.disabled = isLoading;
        exportBtn.disabled = isLoading;
        prevBtn.disabled = isLoading;
        nextBtn.disabled = isLoading;
        
        // Visual feedback
        if (isLoading) {
            applyBtn.style.opacity = '0.6';
            applyBtn.style.cursor = 'not-allowed';
        } else {
            applyBtn.style.opacity = '1';
            applyBtn.style.cursor = 'pointer';
        }
    }

    async function fetchFilteredEnvelopePool() {
        const collected = [];
        const pageLimit = 50;
        let currentOffset = 0;
        const maxConcurrentRequests = 3;
        const fetchQueue = [];

        // Create a queue of fetch operations
        const createFetchTask = (offset) => async () => {
            const params = new URLSearchParams();
            params.set('offset', String(offset));
            params.set('limit', String(pageLimit));
            
            // IMPORTANT: Pass status to API endpoint - this filters server-side
            if (state.status) {
                params.set('status', state.status);
            }
            
            if (state.search) {
                params.set('q', state.search);
            }

            try {
                const response = await fetch('/api/signable/envelopes?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ' while loading envelopes.');
                }

                const payload = await response.json();
                const rawPage = normalizeEnvelopeList(payload);
                
                // Client-side filter by envelope_processed date range ONLY
                // Status filtering is already done by API via status parameter
                const filteredPage = filterEnvelopesByProcessedDate(rawPage, state.dateFrom, state.dateTo);

                return { data: filteredPage, rawLength: rawPage.length };
            } catch (error) {
                throw error;
            }
        };

        // Fetch first page to determine if we need more
        try {
            const firstTask = createFetchTask(0);
            const firstResult = await firstTask();
            collected.push(...firstResult.data);
            
            if (firstResult.rawLength < pageLimit) {
                return collected;
            }

            currentOffset = pageLimit;

            // For remaining pages, fetch in parallel batches
            while (true) {
                const batchTasks = [];
                
                // Create up to maxConcurrentRequests tasks
                for (let i = 0; i < maxConcurrentRequests; i++) {
                    const offset = currentOffset + (i * pageLimit);
                    batchTasks.push({
                        task: createFetchTask(offset),
                        offset: offset
                    });
                }

                // Execute batch in parallel
                const batchResults = await Promise.all(
                    batchTasks.map(item => item.task().catch(err => ({ error: err })))
                );

                let hasMoreData = false;
                
                for (let i = 0; i < batchResults.length; i++) {
                    const result = batchResults[i];
                    
                    if (result.error) {
                        throw result.error;
                    }

                    collected.push(...result.data);
                    
                    if (result.rawLength >= pageLimit) {
                        hasMoreData = true;
                    }
                }

                if (!hasMoreData) {
                    break;
                }

                currentOffset += pageLimit * maxConcurrentRequests;
            }

            return collected;
        } catch (error) {
            throw error;
        }
    }

    function renderRows() {
        rowsEl.innerHTML = '';

        if (!state.envelopes.length) {
            emptyEl.style.display = 'block';
            pagingEl.textContent = 'Showing 0 envelopes';
            batchBtn.disabled = true;
            selectAll.checked = false;
            return;
        }

        emptyEl.style.display = 'none';

        state.envelopes.forEach((env, index) => {
            const fingerprint = getEnvelopeFingerprint(env);
            const title = getEnvelopeTitle(env);
            const contact = getEnvelopeContactName(env);
            const created = getEnvelopeCreatedAt(env);
            const status = getEnvelopeStatus(env);
            const checked = fingerprint && state.selected.has(fingerprint);

            const tr = document.createElement('tr');
            tr.style.animationDelay = String(index * 20) + 'ms';
            tr.innerHTML = [
                '<td><input type="checkbox" ' + (checked ? 'checked' : '') + ' data-role="select" data-fp="' + escapeHtml(fingerprint) + '" aria-label="Select envelope" /></td>',
                '<td>' + escapeHtml(title) + '</td>',
                '<td>' + escapeHtml(contact) + '</td>',
                '<td><span class="status ' + statusClass(status) + '">' + escapeHtml(status || 'unknown') + '</span></td>',
                '<td>' + escapeHtml(created) + '</td>',
                '<td class="mono">' + escapeHtml(shortFingerprint(fingerprint)) + '</td>',
                '<td><div class="row-actions"><button type="button" class="btn btn-soft" data-role="download" data-fp="' + escapeHtml(fingerprint) + '">Download</button></div></td>'
            ].join('');
            rowsEl.appendChild(tr);
        });

        rowsEl.querySelectorAll('[data-role="select"]').forEach((checkbox) => {
            checkbox.addEventListener('change', (event) => {
                const fp = event.currentTarget.getAttribute('data-fp') || '';
                if (!fp) {
                    return;
                }
                if (event.currentTarget.checked) {
                    state.selected.add(fp);
                } else {
                    state.selected.delete(fp);
                    selectAll.checked = false;
                }
                syncBatchButton();
            });
        });

        rowsEl.querySelectorAll('[data-role="download"]').forEach((button) => {
            button.addEventListener('click', async (event) => {
                const fp = event.currentTarget.getAttribute('data-fp') || '';
                if (!fp) {
                    return;
                }
                event.currentTarget.disabled = true;
                event.currentTarget.textContent = 'Resolving...';
                const url = await resolveDownloadUrlByFingerprint(fp);
                if (url) {
                    window.open(url, '_blank', 'noopener');
                    setLiveStatus('Download opened for envelope ' + shortFingerprint(fp) + '.');
                } else {
                    setLiveStatus('No download URL found for envelope ' + shortFingerprint(fp) + '.');
                }
                event.currentTarget.disabled = false;
                event.currentTarget.textContent = 'Download';
            });
        });

        const selectedOnPage = state.envelopes.filter((env) => {
            const fp = getEnvelopeFingerprint(env);
            return fp && state.selected.has(fp);
        }).length;

        selectAll.checked = state.envelopes.length > 0 && selectedOnPage === state.envelopes.length;
        
        const startIndex = state.offset + 1;
        const endIndex = state.offset + state.envelopes.length;
        pagingEl.textContent = 'Showing ' + startIndex + ' to ' + endIndex + ' of ' + state.totalMatched + ' envelopes';
        
        syncBatchButton();
    }

    function syncBatchButton() {
        batchBtn.disabled = state.selected.size === 0 || state.loading;
    }

    function syncPagination() {
        prevBtn.disabled = state.loading || state.offset === 0;
        
        if (state.usingClientFilteredPaging) {
            // Client-side filtering: check against total matched
            nextBtn.disabled = state.loading || (state.offset + state.limit >= state.totalMatched);
        } else {
            // Server-side pagination: check if current page is full
            nextBtn.disabled = state.loading || state.envelopes.length < state.limit;
        }
    }

    function setLiveStatus(message) {
        statusEl.textContent = message;
    }

    function normalizeEnvelopeList(payload) {
        if (Array.isArray(payload)) {
            return payload;
        }

        const candidates = [
            payload?.envelopes,
            payload?.data?.envelopes,
            payload?.data,
            payload?.items,
            payload?.results
        ];

        for (const candidate of candidates) {
            if (Array.isArray(candidate)) {
                return candidate;
            }
        }

        return [];
    }

    function filterEnvelopesByProcessedDate(envelopes, fromDate, toDate) {
        if (!fromDate && !toDate) {
            return envelopes;
        }

        const from = fromDate ? new Date(fromDate + 'T00:00:00Z') : null;
        const to = toDate ? new Date(toDate + 'T23:59:59.999Z') : null;

        return envelopes.filter((env) => {
            // Get envelope_processed timestamp (when the envelope was completed/signed)
            const processedAt = getEnvelopeProcessedDate(env);
            
            // If no processed date, exclude this envelope (not yet signed/completed)
            if (!processedAt) {
                return false;
            }

            if (from && processedAt < from) {
                return false;
            }

            if (to && processedAt > to) {
                return false;
            }

            return true;
        });
    }

    function getEnvelopeProcessedDate(env) {
        // Get the envelope_processed timestamp (ISO 8601 string when envelope was completed)
        const raw = env?.envelope_processed;
        if (!raw) {
            return null;
        }

        const parsed = new Date(raw);
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    function filterEnvelopesByStatus(envelopes, status) {
        if (!status) {
            return envelopes;
        }

        const normalized = status.toLowerCase();
        return envelopes.filter((env) => {
            const current = getEnvelopeStatus(env);

            // "signed" status maps to multiple possible backend states
            if (normalized === 'signed') {
                return ['signed', 'completed', 'complete', 'signed-envelope', 'signed-envelope-complete'].includes(current);
            }

            return current === normalized;
        });
    }

    function filterEnvelopesByDate(envelopes, fromDate, toDate) {
        if (!fromDate && !toDate) {
            return envelopes;
        }

        const from = fromDate ? new Date(fromDate + 'T00:00:00Z') : null;
        const to = toDate ? new Date(toDate + 'T23:59:59.999Z') : null;

        return envelopes.filter((env) => {
            const createdAt = getEnvelopeDateValue(env);
            if (!createdAt) {
                return false;
            }

            if (from && createdAt < from) {
                return false;
            }

            if (to && createdAt > to) {
                return false;
            }

            return true;
        });
    }

    function getEnvelopeDateValue(env) {
        // Signable returns envelope_created as an ISO 8601 string e.g. "2025-07-07T11:30:27+0000"
        const raw = env?.envelope_created || env?.created_at || env?.created || env?.date_created;
        if (!raw) {
            return null;
        }

        const parsed = new Date(raw);
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    async function resolveDownloadUrlByFingerprint(fingerprint) {
        try {
            const response = await fetch('/api/signable/envelopes/' + encodeURIComponent(fingerprint), {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                return null;
            }

            const payload = await response.json();
            return findDownloadUrl(payload);
        } catch {
            return null;
        }
    }

    function findDownloadUrl(node) {
        if (!node) {
            return null;
        }

        if (typeof node === 'string') {
            return isHttp(node) ? node : null;
        }

        if (Array.isArray(node)) {
            for (const item of node) {
                const found = findDownloadUrl(item);
                if (found) {
                    return found;
                }
            }
            return null;
        }

        if (typeof node === 'object') {
            const preferredKeys = [
                'envelope_download_url',
                'download_url',
                'envelope_download',
                'signed_pdf_url',
                'pdf_url',
                'url'
            ];

            for (const key of preferredKeys) {
                if (key in node && typeof node[key] === 'string' && isHttp(node[key])) {
                    return node[key];
                }
            }

            for (const [key, value] of Object.entries(node)) {
                if (/download|pdf/i.test(key) && typeof value === 'string' && isHttp(value)) {
                    return value;
                }
            }

            for (const value of Object.values(node)) {
                const found = findDownloadUrl(value);
                if (found) {
                    return found;
                }
            }
        }

        return null;
    }

    function isHttp(value) {
        return /^https?:\/\//i.test(value);
    }

    function getEnvelopeTitle(env) {
        return env?.envelope_title || env?.title || env?.name || '(Untitled envelope)';
    }

    function getEnvelopeContactName(env) {
        // The list API returns envelope_parties[].party_title as the contact name
        const parties = env?.envelope_parties || env?.parties || env?.signers;
        if (Array.isArray(parties) && parties.length > 0) {
            const name = parties[0]?.party_title || parties[0]?.contact_name ||
                         parties[0]?.name || parties[0]?.full_name;
            if (name) {
                return String(name);
            }
        }
        // Fallback for webhook-style payloads that include top-level contact_name
        if (env?.contact_name) {
            return String(env.contact_name);
        }
        return '-';
    }

    function getEnvelopeStatus(env) {
        return normalizeEnvelopeStatus(env?.envelope_status || env?.status || env?.action || 'unknown');
    }

    function normalizeEnvelopeStatus(value) {
        const normalized = String(value || 'unknown').toLowerCase().trim();

        // Some payloads use alternate labels for a completed signature flow.
        if (['created', 'awaiting'].includes(normalized)) {
            return 'draft';
        }

        return normalized;
    }

    function getEnvelopeCreatedAt(env) {
        const d = getEnvelopeDateValue(env);
        if (!d) {
            return '-';
        }

        return d.toLocaleString();
    }

    function getEnvelopeFingerprint(env) {
        return String(env?.envelope_fingerprint || env?.fingerprint || env?.id || '');
    }

    function shortFingerprint(value) {
        if (!value || value.length <= 20) {
            return value || '-';
        }

        return value.slice(0, 9) + '...' + value.slice(-8);
    }

    function statusClass(status) {
        const normalized = String(status || '').toLowerCase();

        if (['sent', 'signed', 'completed'].includes(normalized)) {
            return 'status-sent';
        }

        if (['draft', 'created', 'awaiting'].includes(normalized)) {
            return 'status-draft';
        }

        if (['cancelled', 'expired', 'declined', 'voided'].includes(normalized)) {
            return 'status-cancelled';
        }

        return 'status-default';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    loadEnvelopes();
})();
</script>
</div>
</x-layouts::app>