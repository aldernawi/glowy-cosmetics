<x-layouts.admin title="إدخال الباركود | Glowy">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-bold text-glowy-rose">الكتالوج</p><h1 class="mt-1 text-3xl font-bold text-glowy-plum">إدخال الباركود</h1><p class="mt-2 max-w-2xl text-sm leading-7 text-glowy-muted">امسحي العلبة في خانة المنتج، فيُحفظ وحده وينتقل المؤشر إلى المنتج التالي. أو ابحثي عن المنتج أولًا ثم امسحي.</p></div>
        <div class="rounded-3xl border border-glowy-champagne bg-white px-6 py-4 text-center"><p class="text-sm text-glowy-muted">تم إدخال</p><p class="mt-1 text-2xl font-bold text-glowy-plum" id="barcode-progress">—</p></div>
    </div>

    <div class="mt-6 grid gap-3 rounded-3xl border border-glowy-champagne bg-white p-5 md:grid-cols-[1fr_auto] md:items-end">
        <label class="grid gap-2 text-sm font-bold">البحث بالاسم أو SKU أو الباركود<input id="barcode-search" type="text" autocomplete="off" spellcheck="false" placeholder="اكتبي اسم المنتج ثم Enter" class="min-h-11 rounded-xl border border-glowy-champagne bg-white px-4"></label>
        <label class="flex min-h-11 items-center gap-2 text-sm font-bold"><input id="barcode-missing" type="checkbox" checked> بدون باركود فقط</label>
    </div>

    <p id="barcode-alert" class="mt-5 rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-800 shadow-sm" style="position:sticky;top:5.25rem;z-index:20" role="alert" hidden></p>

    <div class="mt-5 overflow-x-auto rounded-3xl border border-glowy-champagne bg-white">
        <table class="min-w-full text-right text-sm">
            <thead class="bg-glowy-blush"><tr><th class="p-4">المنتج</th><th class="p-4">SKU</th><th class="p-4">الباركود</th><th class="p-4">الحالة</th></tr></thead>
            <tbody id="barcode-rows"></tbody>
        </table>
        <p id="barcode-empty" class="p-10 text-center text-glowy-muted" hidden></p>
    </div>
    <p id="barcode-more" class="mt-4 text-sm text-glowy-muted" hidden></p>
    <noscript><p class="mt-5 rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-800">هذه الصفحة تحتاج تفعيل JavaScript.</p></noscript>

    <script type="application/json" id="barcode-data">@json($variants)</script>
    <script type="module">
        const $ = (selector) => document.querySelector(selector);
        const variants = JSON.parse($('#barcode-data').textContent);
        const endpoint = @js(route('admin.barcodes.update', ['variant' => '__ID__']));
        const PAGE = 100;
        const statuses = new Map();
        const saving = new Set();

        const normalize = (text) => String(text ?? '').toLowerCase()
            .replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit))
            .replace(/[ً-ْـ]/g, '').replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي').trim();
        const clean = (text) => String(text ?? '').replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit)).trim();
        const label = (variant) => [variant.name, variant.variant].filter(Boolean).join(' — ');

        const renderProgress = () => {
            // Only codes the server has confirmed count as entered.
            $('#barcode-progress').textContent = `${variants.filter((variant) => variant.barcode && !saving.has(variant.id)).length} من ${variants.length}`;
        };

        const showAlert = (message) => {
            $('#barcode-alert').hidden = !message;
            $('#barcode-alert').textContent = message ?? '';
        };

        const visible = () => {
            const words = normalize($('#barcode-search').value).split(/\s+/).filter(Boolean);
            // Searching must also find products that already have a code.
            const onlyMissing = $('#barcode-missing').checked && words.length === 0;
            return variants.filter((variant) => (!onlyMissing || !variant.barcode)
                && words.every((word) => normalize([variant.name, variant.name_en, variant.variant, variant.brand, variant.sku, variant.barcode].filter(Boolean).join(' ')).includes(word)));
        };

        const paintStatus = (variant, row = $(`[data-variant="${variant.id}"]`)) => {
            if (!row) return;
            const status = statuses.get(variant.id) ?? (variant.barcode ? { text: 'مسجل', tone: 'done' } : { text: 'بدون باركود', tone: 'none' });
            const cell = row.querySelector('[data-status]');
            cell.textContent = status.text;
            cell.className = `p-4 font-bold ${{ done: 'text-emerald-700', error: 'text-red-700', saving: 'text-glowy-muted', none: 'text-glowy-muted' }[status.tone]}`;
            row.classList.toggle('bg-emerald-50', status.tone === 'done' && statuses.has(variant.id));
            row.classList.toggle('bg-red-50', status.tone === 'error');
        };

        const setStatus = (variant, text, tone) => {
            statuses.set(variant.id, { text, tone });
            paintStatus(variant);
        };

        const render = () => {
            const matches = visible();
            $('#barcode-rows').replaceChildren(...matches.slice(0, PAGE).map((variant) => {
                const row = document.createElement('tr');
                row.dataset.variant = variant.id;
                row.className = 'border-t border-glowy-champagne/60';
                const name = document.createElement('td');
                name.className = 'p-4';
                const title = document.createElement('strong');
                title.textContent = variant.name;
                const details = document.createElement('span');
                details.className = 'block text-xs text-glowy-muted';
                details.textContent = [variant.variant, variant.brand].filter(Boolean).join(' · ');
                name.append(title, details);
                const sku = document.createElement('td');
                sku.className = 'p-4';
                const code = document.createElement('span');
                code.dir = 'ltr';
                code.textContent = variant.sku;
                sku.append(code);
                const field = document.createElement('td');
                field.className = 'p-4';
                const input = document.createElement('input');
                input.dir = 'ltr';
                input.autocomplete = 'off';
                input.spellcheck = false;
                input.maxLength = 64;
                input.value = variant.barcode ?? '';
                input.placeholder = 'امسحي هنا';
                input.setAttribute('aria-label', `باركود ${label(variant)}`);
                input.className = 'min-h-11 w-full rounded-xl border border-glowy-champagne bg-white px-4';
                input.style.minWidth = '13rem';
                field.append(input);
                const status = document.createElement('td');
                status.dataset.status = '';
                row.append(name, sku, field, status);
                paintStatus(variant, row);
                return row;
            }));
            $('#barcode-empty').hidden = matches.length > 0;
            $('#barcode-empty').textContent = $('#barcode-search').value.trim() ? 'لا يوجد منتج مطابق.' : 'كل المنتجات لها باركود.';
            $('#barcode-more').hidden = matches.length <= PAGE;
            $('#barcode-more').textContent = `يظهر أول ${PAGE} من ${matches.length}. تظهر البقية كلما أدخلتِ الباركود، أو استخدمي البحث.`;
        };

        const focusAfter = (row) => {
            if ($('#barcode-search').value.trim()) {
                $('#barcode-search').value = '';
                render();
                $('#barcode-search').focus();
                return;
            }
            let next = row.nextElementSibling;
            if (!next && $('#barcode-missing').checked) {
                render();
                next = $('#barcode-rows tr');
            }
            (next?.querySelector('input') ?? $('#barcode-search')).focus();
            next?.querySelector('input')?.select();
        };

        const request = async (variant, barcode) => {
            const response = await fetch(endpoint.replace('__ID__', variant.id), {
                method: 'PATCH',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').content },
                body: JSON.stringify({ barcode: barcode || null }),
            });
            const data = await response.json().catch(() => ({}));
            if (response.status === 419 || response.status === 401) throw new Error('انتهت الجلسة. حدّثي الصفحة ثم أعيدي المسح.');
            if (!response.ok) throw new Error(data.errors?.barcode?.[0] ?? data.message ?? 'تعذر الحفظ.');
            return data.barcode;
        };

        // Moving on immediately keeps up with the scanner; a failed row turns red and keeps its value.
        const save = async (row, { advance }) => {
            const variant = variants.find((item) => item.id === Number(row.dataset.variant));
            const input = row.querySelector('input');
            const barcode = clean(input.value);
            if (barcode === (variant.barcode ?? '')) {
                if (advance && barcode) focusAfter(row);
                return;
            }
            const owner = barcode && variants.find((item) => item.id !== variant.id && item.barcode?.toLowerCase() === barcode.toLowerCase());
            if (owner) {
                showAlert(`الباركود ${barcode} مسجل للمنتج «${label(owner)}». لم يُحفظ للمنتج «${label(variant)}».`);
                setStatus(variant, 'مكرر — لم يُحفظ', 'error');
                input.value = variant.barcode ?? '';
                input.focus();
                input.select();
                // Already focused, so focusin does not centre the row again.
                row.scrollIntoView({ block: 'center' });
                return;
            }
            showAlert(null);
            const previous = variant.barcode;
            variant.barcode = barcode || null;
            input.value = barcode;
            saving.add(variant.id);
            setStatus(variant, 'جارٍ الحفظ…', 'saving');
            renderProgress();
            if (advance) focusAfter(row);
            try {
                variant.barcode = await request(variant, barcode);
                setStatus(variant, variant.barcode ? 'تم الحفظ ✓' : 'حُذف الباركود', variant.barcode ? 'done' : 'none');
            } catch (error) {
                variant.barcode = previous;
                const message = error instanceof TypeError ? 'لا يوجد اتصال. أعيدي المسح عند رجوع الإنترنت.' : error.message;
                setStatus(variant, 'لم يُحفظ', 'error');
                showAlert(`«${label(variant)}»: ${message}`);
            }
            saving.delete(variant.id);
            renderProgress();
        };

        $('#barcode-rows').addEventListener('keydown', (event) => {
            const row = event.target.closest('tr');
            if (!row || event.target.tagName !== 'INPUT') return;
            if (event.key === 'Enter' || (event.key === 'Tab' && !event.shiftKey && event.target.value.trim())) {
                event.preventDefault();
                void save(row, { advance: true });
            } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                (event.key === 'ArrowDown' ? row.nextElementSibling : row.previousElementSibling)?.querySelector('input').focus();
            } else if (event.key === 'Escape') {
                $('#barcode-search').focus();
            }
        });
        $('#barcode-rows').addEventListener('change', (event) => { if (event.target.tagName === 'INPUT') void save(event.target.closest('tr'), { advance: false }); });
        $('#barcode-rows').addEventListener('focusin', (event) => { if (event.target.tagName !== 'INPUT') return;
            event.target.select();
            event.target.closest('tr').scrollIntoView({ block: 'center' });
        });
        $('#barcode-search').addEventListener('input', render);
        $('#barcode-search').addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const input = $('#barcode-rows input');
            if ($('#barcode-search').value.trim() && input) input.focus();
        });
        $('#barcode-missing').addEventListener('change', () => { render(); $('#barcode-rows input')?.focus(); });

        window.addEventListener('beforeunload', (event) => { if (saving.size > 0 || [...statuses.values()].some((status) => status.text === 'لم يُحفظ')) event.preventDefault(); });

        renderProgress();
        render();
        ($('#barcode-rows input') ?? $('#barcode-search')).focus();
    </script>
</x-layouts.admin>
