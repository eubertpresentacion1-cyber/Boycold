(function () {
    if (window.__boycoldOrderNotifyReady) return;
    window.__boycoldOrderNotifyReady = true;

    const ORDER_API = '../online-orders-api.php';
    const ORDER_POPUP = '../order-popup.php';
    const POLL_INTERVAL = 5000;

    let latestOrderId = 0;
    let isInitialPoll = true;
    let popupSound = null;
    let popupSoundInterval = null;

    function ensurePopupHost() {
        let popupHost = document.getElementById('popupHost');
        if (!popupHost) {
            popupHost = document.createElement('div');
            popupHost.id = 'popupHost';
            popupHost.style.display = 'none';
            document.body.appendChild(popupHost);
        }
        return popupHost;
    }

    function createPopupSound() {
        if (!popupSound) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return null;
            popupSound = new AudioCtx();
        }
        return popupSound;
    }

    function playPopupSoundTick() {
        const ctx = createPopupSound();
        if (!ctx) return;
        if (ctx.state === 'suspended') {
            ctx.resume().catch(() => {});
        }

        const now = ctx.currentTime;
        [0, 0.22].forEach((offset, index) => {
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillator.type = 'triangle';
            oscillator.frequency.value = index === 0 ? 880 : 1175;
            gain.gain.setValueAtTime(0.0001, now + offset);
            gain.gain.exponentialRampToValueAtTime(0.1, now + offset + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.18);
            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.start(now + offset);
            oscillator.stop(now + offset + 0.2);
        });
    }

    function startPopupSound() {
        if (popupSoundInterval) return;
        playPopupSoundTick();
        popupSoundInterval = setInterval(playPopupSoundTick, 1100);
    }

    function stopPopupSound() {
        if (!popupSoundInterval) return;
        clearInterval(popupSoundInterval);
        popupSoundInterval = null;
    }

    window.orderPopupSoundControl = {
        start: startPopupSound,
        stop: stopPopupSound
    };

    document.addEventListener('click', () => {
        if (popupSoundInterval && popupSound?.state === 'suspended') {
            popupSound.resume().catch(() => {});
        }
    });

    function findNotificationButton() {
        return document.getElementById('notifBtn') ||
            document.querySelector('.top-header .icon-btn, header.top-header .icon-btn');
    }

    function buildNotificationItem(title, subtitle, iconClass) {
        const item = document.createElement('div');
        item.className = 'notif-item unread';
        item.innerHTML = `
            <div class="notif-icon ${iconClass}"><i class="fa-solid fa-bag-shopping"></i></div>
            <div class="notif-content">
                <p class="notif-item-title"></p>
                <p class="notif-item-sub"></p>
            </div>
            <div class="notif-time">
                <span class="notif-time-main">Now</span>
                <span class="notif-time-sub">Just now</span>
            </div>
        `;
        item.querySelector('.notif-item-title').textContent = title;
        item.querySelector('.notif-item-sub').textContent = subtitle;
        return item;
    }

    function buildDropdownMarkup(hasBadgeCount) {
        const items = hasBadgeCount ? `
            <div class="notif-item unread">
                <div class="notif-icon notif-icon-bag"><i class="fa-solid fa-bag-shopping"></i></div>
                <div class="notif-content">
                    <p class="notif-item-title">New online order received</p>
                    <p class="notif-item-sub">Order #0001</p>
                </div>
                <div class="notif-time">
                    <span class="notif-time-main">10:30 am</span>
                    <span class="notif-time-sub">Just now</span>
                </div>
            </div>
            <div class="notif-item unread">
                <div class="notif-icon notif-icon-card"><i class="fa-solid fa-credit-card"></i></div>
                <div class="notif-content">
                    <p class="notif-item-title">Payment Confirmed</p>
                    <p class="notif-item-sub">Order #0003</p>
                </div>
                <div class="notif-time">
                    <span class="notif-time-main">10:30 am</span>
                    <span class="notif-time-sub">Just now</span>
                </div>
            </div>
        ` : '<div class="notif-empty">No new notifications</div>';

        return `
            <div class="notif-header">
                <span class="notif-title">Notifications</span>
                <a href="#" class="notif-mark-read" id="markAllRead">Mark all as read</a>
            </div>
            <div class="notif-list" id="notifList">${items}</div>
            <a href="pos-online.php" class="notif-footer">
                View all notifications <i class="fa-solid fa-chevron-right"></i>
            </a>
        `;
    }

    function ensureNotificationUi() {
        const existingBtn = document.getElementById('notifBtn');
        const existingDropdown = document.getElementById('notifDropdown');
        const shouldBindDropdown = !(existingBtn && existingDropdown);

        const notifBtn = findNotificationButton();
        if (!notifBtn) return null;

        if (!notifBtn.id) notifBtn.id = 'notifBtn';
        notifBtn.setAttribute('aria-label', 'Notifications');
        notifBtn.setAttribute('type', 'button');

        let notifBadge = notifBtn.querySelector('.icon-badge');
        if (!notifBadge) {
            notifBadge = document.createElement('span');
            notifBadge.className = 'icon-badge';
            notifBadge.textContent = '0';
            notifBadge.style.display = 'none';
            notifBtn.appendChild(notifBadge);
        }
        if (!notifBadge.id) notifBadge.id = 'notifBadge';

        let notifWrap = notifBtn.closest('.notif-wrap');
        if (!notifWrap) {
            notifWrap = document.createElement('div');
            notifWrap.className = 'notif-wrap';
            notifBtn.parentNode.insertBefore(notifWrap, notifBtn);
            notifWrap.appendChild(notifBtn);
        }

        let notifDropdown = document.getElementById('notifDropdown');
        if (!notifDropdown) {
            const badgeCount = parseInt(notifBadge.textContent || '0', 10);
            notifDropdown = document.createElement('div');
            notifDropdown.className = 'notif-dropdown';
            notifDropdown.id = 'notifDropdown';
            notifDropdown.innerHTML = buildDropdownMarkup(badgeCount > 0);
            notifWrap.appendChild(notifDropdown);
        }

        return {
            btn: notifBtn,
            dropdown: notifDropdown,
            badge: document.getElementById('notifBadge'),
            list: document.getElementById('notifList'),
            markAllRead: document.getElementById('markAllRead'),
            shouldBindDropdown
        };
    }

    function bindGeneratedDropdown(ui) {
        if (!ui || !ui.shouldBindDropdown || ui.btn.dataset.orderNotifyBound === 'true') return;

        ui.btn.addEventListener('click', (event) => {
            event.stopPropagation();
            ui.dropdown.classList.toggle('open');
        });

        document.addEventListener('click', (event) => {
            if (!ui.dropdown.contains(event.target) && !ui.btn.contains(event.target)) {
                ui.dropdown.classList.remove('open');
            }
        });

        ui.markAllRead?.addEventListener('click', (event) => {
            event.preventDefault();
            ui.list?.querySelectorAll('.notif-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            if (ui.badge) ui.badge.style.display = 'none';
        });

        ui.btn.dataset.orderNotifyBound = 'true';
    }

    function addNotification(order) {
        const ui = ensureNotificationUi();
        if (!ui?.badge || !ui?.list) return;

        ui.list.querySelector('.notif-empty')?.remove();
        const item = buildNotificationItem(
            'New online order received',
            `Order #${order.id}`,
            'notif-icon-bag'
        );
        ui.list.insertBefore(item, ui.list.firstChild);

        ui.badge.style.display = 'flex';
        ui.badge.textContent = (parseInt(ui.badge.textContent || '0', 10) + 1).toString();
    }

    function showPopup(orderId) {
        if (!orderId) return;
        const popupHost = ensurePopupHost();
        const orderIdText = String(orderId);
        const alreadyShowing = Array.from(popupHost.querySelectorAll('.order-popup-frame'))
            .some(frame => frame.dataset.orderId === orderIdText);
        if (alreadyShowing) return;

        const iframe = document.createElement('iframe');
        iframe.src = `${ORDER_POPUP}?order_id=${encodeURIComponent(orderIdText)}`;
        iframe.dataset.orderId = orderIdText;
        iframe.className = 'order-popup-frame';
        popupHost.innerHTML = '';
        popupHost.appendChild(iframe);
        popupHost.style.display = 'block';
        startPopupSound();
    }

    function closePopup() {
        const popupHost = ensurePopupHost();
        stopPopupSound();
        popupHost.innerHTML = '';
        popupHost.style.display = 'none';
    }

    async function refreshOnlineOrdersTable() {
        // Only pos-online.php has this table. On any other page (menu,
        // history, shift, etc.) there's nothing to refresh, so bail out.
        const ordersBody = document.getElementById('ordersBody');
        if (!ordersBody) return;

        try {
            const res = await fetch(window.location.href, { cache: 'no-store' });
            const html = await res.text();
            const freshDoc = new DOMParser().parseFromString(html, 'text/html');
            const freshBody = freshDoc.getElementById('ordersBody');
            if (!freshBody) return;

            ordersBody.innerHTML = freshBody.innerHTML;

            // Re-apply whichever tab filter (All / Confirmed / Preparing / etc.)
            // was active before the refresh, so the new row respects it.
            const activeTab = document.querySelector('.order-tabs a.active');
            const filter = activeTab?.dataset.filter || 'all';
            if (typeof window.applyStatusFilter === 'function') {
                window.applyStatusFilter(filter);
            }
        } catch (err) {
            console.error('Failed to refresh online orders table', err);
        }
    }

    window.addEventListener('message', (event) => {
        const type = event.data?.type;
        if (['orderAccepted', 'orderCancelled', 'closeOrderPopup', 'orderUpdated'].includes(type)) {
            closePopup();
        }
        if (['orderAccepted', 'orderCancelled', 'orderUpdated'].includes(type)) {
            refreshOnlineOrdersTable();
        }
    });

    async function pollOnlineOrders() {
        try {
            const res = await fetch(`${ORDER_API}?last_order_id=${encodeURIComponent(latestOrderId)}`);
            const data = await res.json();
            if (!data.success || !Array.isArray(data.orders)) return;

            if (isInitialPoll) {
                latestOrderId = data.latest_order_id || latestOrderId;
                isInitialPoll = false;
                return;
            }

            if (data.orders.length > 0) {
                latestOrderId = data.latest_order_id || latestOrderId;
                data.orders.forEach((order) => {
                    showPopup(order.id);
                    addNotification(order);
                });
            }
        } catch (err) {
            console.error('Online order poll failed', err);
        }
    }

    bindGeneratedDropdown(ensureNotificationUi());
    setInterval(pollOnlineOrders, POLL_INTERVAL);
    pollOnlineOrders();
})();