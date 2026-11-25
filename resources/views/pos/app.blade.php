<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#3A2F33">
    <title>كاشير Glowy</title>
    <link rel="manifest" href="/pos-assets/manifest.webmanifest">
    <link rel="icon" href="/images/glowy-logo.png">
    <link rel="stylesheet" href="/pos-assets/app.css">
    <style id="page-size" media="print"></style>
    <script type="module" src="/pos-assets/app.js"></script>
</head>
<body>
    <header class="bar">
        <span class="bar__logo"><img src="/images/glowy-logo.png" alt="Glowy"></span>
        <dl class="bar__info">
            <div><dt>فاتورة رقم</dt><dd id="next-number" dir="ltr">—</dd></div>
            <div><dt>الكاشير</dt><dd id="cashier">—</dd></div>
            <div><dt>الوقت</dt><dd id="clock">—</dd></div>
        </dl>
        <div class="bar__status" role="status" aria-live="polite">
            <span class="pending" id="pending" hidden></span>
            <span class="connection" id="connection" data-state="checking"><span class="dot" aria-hidden="true"></span><span id="connection-text">جارٍ التحقق…</span></span>
        </div>
    </header>
    <p class="notice" id="notice" role="alert" hidden></p>

    <main class="layout" id="register" hidden>
        <section class="invoice" aria-label="الفاتورة">
            <div class="scan">
                <svg class="scan__icon" viewBox="0 0 32 24" aria-hidden="true"><path d="M2 2v20M6 2v20M10 2v14M13 2v20M17 2v14M20 2v20M24 2v14M27 2v20M30 2v20" stroke="currentColor" stroke-width="2"/></svg>
                <label class="visually-hidden" for="search">امسحي الباركود أو ابحثي</label>
                <input id="search" type="text" placeholder="امسحي الباركود أو اكتبي اسم المنتج" autocomplete="off" spellcheck="false" autofocus>
                <button type="button" class="scan__clear" id="clear-search" aria-label="مسح البحث" hidden>×</button>
            </div>
            <div class="table" id="table-scroll">
                <table>
                    <thead><tr><th class="c-index">م</th><th>الصنف</th><th class="c-num">السعر</th><th class="c-qty">الكمية</th><th class="c-num">الإجمالي</th><th class="c-remove"><span class="visually-hidden">حذف</span></th></tr></thead>
                    <tbody id="cart-items"></tbody>
                </table>
                <div class="table__empty" id="cart-empty">
                    <p class="table__empty-title">الفاتورة فارغة</p>
                    <p>امسحي باركود المنتج، أو اختاريه من القائمة.</p>
                    <div class="last-sale" id="last-sale" hidden></div>
                </div>
            </div>
            <div class="checkout">
                <div class="checkout__total">
                    <p class="checkout__count"><span>عدد القطع</span><strong id="pieces">0</strong></p>
                    <p class="checkout__amount"><span>الإجمالي</span><strong><span id="total">0.000</span> <small>د.ل</small></strong></p>
                </div>
                <div class="checkout__methods" role="group" aria-label="الدفع">
                    <button type="button" class="pay pay--cash" data-pay="cash" disabled><span>نقدًا</span><kbd>F9</kbd></button>
                    <button type="button" class="pay" data-pay="card" disabled><span>بطاقة</span><kbd>F10</kbd></button>
                    <button type="button" class="pay" data-pay="transfer" disabled><span>تحويل</span><kbd>F8</kbd></button>
                </div>
            </div>
        </section>

        <section class="catalog" aria-label="المنتجات">
            <div class="tabs" id="categories" role="group" aria-label="التصنيفات"></div>
            <div class="grid" id="results"></div>
            <p class="catalog__empty" id="results-empty" hidden>لا توجد منتجات مطابقة.</p>
        </section>
    </main>

    <footer class="keys" id="keys" hidden>
        <button type="button" data-action="search"><kbd>F2</kbd> بحث</button>
        <button type="button" data-action="history"><kbd>F3</kbd> المبيعات</button>
        <button type="button" data-action="clear"><kbd>F4</kbd> إلغاء الفاتورة</button>
        <button type="button" data-action="plus"><kbd>+</kbd> زيادة</button>
        <button type="button" data-action="minus"><kbd>−</kbd> إنقاص</button>
        <button type="button" data-action="remove"><kbd>Del</kbd> حذف الصنف</button>
        <button type="button" data-action="sync" id="sync-button"><kbd>F7</kbd> مزامنة</button>
    </footer>

    <main class="unlinked" id="unlinked" hidden>
        <h1>هذا الكمبيوتر غير مربوط بعد</h1>
        <p>يلزم الإنترنت مرة واحدة فقط لربط الجهاز وتنزيل المنتجات.</p>
        <a class="button button--primary" href="/admin/pos/device">ربط الجهاز من لوحة الإدارة</a>
    </main>

    <dialog id="pay-dialog" aria-labelledby="pay-title">
        <form method="dialog" id="pay-form">
            <header class="dialog__head"><h2 id="pay-title">الدفع</h2><button type="button" class="dialog__close" data-close aria-label="رجوع">×</button></header>
            <div class="dialog__body pay-grid">
                <div class="pay-main">
                    <p class="due"><span>المطلوب</span><strong id="pay-total"></strong></p>
                    <fieldset class="methods"><legend class="visually-hidden">طريقة الدفع</legend>
                        <label><input type="radio" name="method" value="cash" checked><span>نقدًا</span></label>
                        <label><input type="radio" name="method" value="card"><span>بطاقة</span></label>
                        <label><input type="radio" name="method" value="transfer"><span>تحويل</span></label>
                    </fieldset>
                    <div id="cash-fields">
                        <label class="field" for="received">المبلغ المستلم</label>
                        <input class="money" id="received" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="المبلغ مضبوط">
                        <p class="change" id="change-row"><span id="change-label">الباقي للزبونة</span><strong id="change">—</strong></p>
                    </div>
                    <div id="reference-field" hidden>
                        <label class="field" for="reference">رقم المرجع (اختياري)</label>
                        <input class="money" id="reference" dir="ltr" maxlength="255" autocomplete="off">
                    </div>
                    <p class="error" id="pay-error" role="alert" hidden></p>
                </div>
                <div class="cash" id="cash-keys">
                    <div class="notes" id="notes" role="group" aria-label="فئات نقدية"></div>
                    <div class="pad" id="pad" role="group" aria-label="لوحة أرقام"></div>
                </div>
            </div>
            <footer class="dialog__foot">
                <label class="check"><input type="checkbox" id="print-receipt" checked> طباعة الإيصال</label>
                <button type="submit" class="button button--primary button--large" id="confirm-sale">تأكيد البيع <kbd>Enter</kbd></button>
            </footer>
        </form>
    </dialog>

    <dialog id="history-dialog" aria-labelledby="history-title">
        <header class="dialog__head"><h2 id="history-title">مبيعات هذا الجهاز</h2><button type="button" class="dialog__close" data-close aria-label="إغلاق">×</button></header>
        <div class="dialog__body">
            <div class="summary" id="summary"></div>
            <div class="history"><table><thead><tr><th>الفاتورة</th><th>الوقت</th><th>القطع</th><th>الدفع</th><th>الإجمالي</th><th>الحالة</th><th></th></tr></thead><tbody id="history"></tbody></table></div>
        </div>
    </dialog>

    <dialog id="confirm-dialog" aria-labelledby="confirm-text">
        <div class="dialog__body"><p class="confirm" id="confirm-text">إلغاء الفاتورة الحالية وحذف كل أصنافها؟</p></div>
        <footer class="dialog__foot"><button type="button" class="button" data-close>لا، رجوع</button><button type="button" class="button button--danger" id="confirm-clear">نعم، إلغاء الفاتورة</button></footer>
    </dialog>

    <div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
    <section class="receipt" id="receipt" aria-hidden="true"></section>
</body>
</html>
