document.addEventListener('DOMContentLoaded', function () {
    // 1. Navigation Logic
    const navItems = document.querySelectorAll('.nav-item');
    const views = document.querySelectorAll('.view');
    const pageTitle = document.getElementById('page-title');

    navItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const viewId = this.getAttribute('data-view');

            navItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            views.forEach(v => v.classList.remove('active'));
            document.getElementById('view-' + viewId).classList.add('active');

            pageTitle.innerText = this.querySelector('span').innerText;

            // Load view-specific data
            loadViewData(viewId);
        });
    });

    // 2. Load View Data
    function loadViewData(viewId) {
        if (viewId === 'dashboard') loadDashboard();
        if (viewId === 'events') loadEvents();
        if (viewId === 'issue') loadEventSelect();
    }

    // --- Dashboard ---
    function loadDashboard() {
        fetch('api.php?action=get_stats')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('stat-total').innerText = data.stats.total + ' 개';
                    document.getElementById('stat-used').innerText = data.stats.used + ' 개';
                    document.getElementById('stat-pending').innerText = data.stats.pending + ' 개';

                    const tbody = document.querySelector('#recent-coupons tbody');
                    tbody.innerHTML = '';
                    data.recent.forEach(c => {
                        const statusClass = c.status === 'ISSUED' ? 'status-issued' : 'status-used';
                        const statusText = c.status === 'ISSUED' ? '발행완료' : '사용완료';
                        tbody.innerHTML += `
                        <tr>
                            <td>${c.customer_name}</td>
                            <td>${c.event_name}</td>
                            <td><code>${c.coupon_code}</code></td>
                            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                            <td>${c.issued_at.split(' ')[0]}</td>
                            <td>${c.expiration_date}</td>
                        </tr>
                    `;
                    });
                }
            });
    }

    // --- Events ---
    function loadEvents() {
        fetch('api.php?action=get_events')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.querySelector('#event-table tbody');
                    tbody.innerHTML = '';
                    data.events.forEach(e => {
                        const unit = e.discount_type === 'PERCENT' ? '%' : '원';
                        tbody.innerHTML += `
                        <tr>
                            <td>${e.id}</td>
                            <td><strong>${e.event_name}</strong></td>
                            <td>${e.discount_target || '-'}</td>
                            <td>${parseInt(e.discount_value).toLocaleString()}${unit}</td>
                            <td>${e.valid_days}일</td>
                            <td>
                                <div style="display:flex; gap:0.5rem">
                                    <button onclick="editEvent(${e.id})" class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.8rem">수정</button>
                                    <button onclick="viewEventCoupons(${e.id})" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem">쿠폰발행조회</button>
                                </div>
                            </td>
                        </tr>
                    `;
                    });
                }
            });
    }

    // --- Issue Coupon ---
    function loadEventSelect() {
        fetch('api.php?action=get_events')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('issue-event-id');
                    select.innerHTML = '<option value="">이벤트를 선택하세요</option>';
                    data.events.forEach(e => {
                        const unit = e.discount_type === 'PERCENT' ? '%' : '원';
                        select.innerHTML += `<option value="${e.id}">${e.event_name} (${e.discount_target || '-'} ${parseInt(e.discount_value).toLocaleString()}${unit})</option>`;
                    });
                }
            });
    }

    // --- Submitting Event ---
    const eventForm = document.getElementById('event-form');
    if (eventForm) {
        eventForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const eventId = document.getElementById('event-id').value;
            const payload = {
                id: eventId,
                name: document.getElementById('event-name').value,
                target: document.getElementById('event-target').value,
                type: document.getElementById('event-type').value,
                value: document.getElementById('event-value').value,
                valid_days: document.getElementById('event-valid-days').value,
                description: document.getElementById('event-desc').value,
                template: document.getElementById('event-template').value
            };

            const action = eventId ? 'update_event' : 'add_event';

            fetch(`api.php?action=${action}`, {
                method: 'POST',
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(eventId ? '수정되었습니다.' : '이벤트가 등록되었습니다.');
                        closeModal('event-modal');
                        loadEvents();
                    }
                });
        });
    }

    // --- Submitting Issue ---
    const issueForm = document.getElementById('issue-form');
    if (issueForm) {
        issueForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const payload = {
                event_id: document.getElementById('issue-event-id').value,
                customer_name: document.getElementById('issue-customer-name').value,
                phone: document.getElementById('issue-phone').value
            };

            fetch('api.php?action=issue_coupon', {
                method: 'POST',
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('issue-result').style.display = 'block';
                        document.getElementById('copy-text').value = data.message;
                        // Reset form
                        issueForm.reset();
                        lucide.createIcons();
                    }
                });
        });
    }

    // Initial Load
    loadDashboard();
});

// Global functions
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function openNewEventModal() {
    const defaultTemplate = `안녕하세요 텐트깔끄미 입니다. 

[이벤트명] 행사에 참가해 주셔서 감사합니다. 
 
고객님께 드리는 혜택은 [할인대상] [할인수치][할인유형] 입니다. 
해당 할인권은 [유효기간] 까지 사용 가능하십니다. 

할인권을 사용하고자 하실때는 서비스 결제전에 '카카오톡 @텐트깔끄미' 계정으로 상담을 부탁 드립니다.

쿠폰 번호는 [쿠폰번호] 입니다. 

항상 텐트깔끄미를 이용해 주셔서 감사드립니다.`;

    document.getElementById('event-form').reset();
    document.getElementById('event-id').value = '';
    document.getElementById('event-desc').value = ''; // Ensure memo is empty
    document.getElementById('modal-title').innerText = '새 이벤트 등록';
    document.getElementById('event-submit-btn').innerText = '저장하기';
    document.getElementById('event-template').value = defaultTemplate;
    document.getElementById('event-valid-days').value = 50; // Default to 50 as requested

    openModal('event-modal');
}

function editEvent(id) {
    fetch('api.php?action=get_event&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const e = data.event;
                document.getElementById('event-id').value = e.id;
                document.getElementById('event-name').value = e.event_name;
                document.getElementById('event-target').value = e.discount_target || '기타';
                document.getElementById('event-type').value = e.discount_type;
                document.getElementById('event-value').value = e.discount_value;
                document.getElementById('event-valid-days').value = e.valid_days;
                document.getElementById('event-desc').value = e.description;
                document.getElementById('event-template').value = e.msg_template || '';

                document.getElementById('modal-title').innerText = '이벤트 수정';
                document.getElementById('event-submit-btn').innerText = '수정사항 저장';
                openModal('event-modal');
            }
        });
}

let currentEventIdForList = 0;
function viewEventCoupons(id) {
    currentEventIdForList = id;
    document.getElementById('coupon-list-search').value = '';
    fetchCouponsForModal(id, '');
    openModal('coupon-list-modal');
}

function fetchCouponsForModal(id, query) {
    fetch(`api.php?action=get_coupons_by_event&id=${id}&q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const tbody = document.querySelector('#modal-coupon-table tbody');
                tbody.innerHTML = '';
                data.coupons.forEach(c => {
                    const statusClass = c.status === 'ISSUED' ? 'status-issued' : 'status-used';
                    const statusText = c.status === 'ISSUED' ? '발행완료' : '사용완료';
                    tbody.innerHTML += `
                    <tr>
                        <td>${c.customer_name}</td>
                        <td>${c.phone_number}</td>
                        <td><code>${c.coupon_code}</code></td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>${c.issued_at.split(' ')[0]}</td>
                    </tr>
                `;
                });
            }
        });
}

function searchCouponsInModal() {
    const q = document.getElementById('coupon-list-search').value;
    fetchCouponsForModal(currentEventIdForList, q);
}

function copyToClipboard() {
    const copyText = document.getElementById('copy-text');
    copyText.select();
    document.execCommand('copy');
    alert('문구가 복사되었습니다! 카카오톡이나 문자에 붙여넣으세요.');
}

// Verification Logic
function verifyCoupon() {
    const code = document.getElementById('verify-code').value.trim();
    if (!code) return alert('쿠폰번호를 입력해 주세요.');

    fetch('api.php?action=verify_coupon&code=' + code)
        .then(res => res.json())
        .then(data => {
            const resultArea = document.getElementById('verify-result');
            resultArea.style.display = 'block';

            if (data.success) {
                const cp = data.coupon;
                const statusClass = cp.status === 'ISSUED' ? 'status-issued' : 'status-used';
                const statusText = cp.status === 'ISSUED' ? '사용가능(발행완료)' : '사용완료(할인적용됨)';
                const useBtn = cp.status === 'ISSUED' ? `<button onclick="useCoupon('${cp.coupon_code}')" class="btn btn-primary btn-large" style="margin-top: 1.5rem">할인권 사용 처리하기</button>` : '';

                resultArea.innerHTML = `
                <div class="verify-detail">
                    <h3 style="margin-bottom: 1rem">조회 결과: <span class="status-badge ${statusClass}">${statusText}</span></h3>
                    <p><strong>이벤트:</strong> ${cp.event_name}</p>
                    <p><strong>고객명:</strong> ${cp.customer_name}</p>
                    <p><strong>연락처:</strong> ${cp.phone_number}</p>
                    <p><strong>유효기간:</strong> ${cp.expiration_date} 까지</p>
                    ${useBtn}
                </div>
            `;
            } else {
                resultArea.innerHTML = `<div class="error-msg">${data.message}</div>`;
            }
        });
}

function useCoupon(code) {
    if (!confirm('정말로 이 할인권을 사용 처리하시겠습니까? (취소 불가)')) return;

    fetch('api.php?action=use_coupon', {
        method: 'POST',
        body: JSON.stringify({ code: code })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('할인권 사용이 완료되었습니다!');
                verifyCoupon(); // Refresh
            } else {
                alert(data.message);
            }
        });
}
