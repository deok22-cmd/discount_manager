<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⛺ 텐트깔끄미 | 할인권 발행 시스템</title>
    <link rel="stylesheet" href="style.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;600;800&family=Outfit:wght@400;700&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="/discount/" class="logo">
                    <span class="icon">⛺</span>
                    <span class="text">텐트깔끄미<br>할인권관리</span>
                </a>
            </div>
            <nav class="nav-menu">
                <a href="#" class="nav-item active" data-view="dashboard">
                    <i data-lucide="layout-dashboard"></i>
                    <span>대시보드</span>
                </a>
                <a href="#" class="nav-item" data-view="events">
                    <i data-lucide="calendar"></i>
                    <span>이벤트 관리</span>
                </a>
                <a href="#" class="nav-item" data-view="issue">
                    <i data-lucide="ticket"></i>
                    <span>할인권 발행</span>
                </a>
                <a href="#" class="nav-item" data-view="verify">
                    <i data-lucide="scan"></i>
                    <span>쿠폰 조회/사용</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i data-lucide="log-out"></i>
                    <span>시스템 로그아웃</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <h1 id="page-title">대시보드</h1>
                <div class="user-info">
                    <span class="admin-badge">Admin Mode</span>
                    <strong>deok22</strong>
                </div>
            </header>

            <div id="view-container" class="view-container">
                <!-- Dashboard View -->
                <section id="view-dashboard" class="view active">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i data-lucide="ticket"></i></div>
                            <div class="stat-info">
                                <h3>전체 발행권</h3>
                                <p id="stat-total">0 개</p>
                            </div>
                        </div>
                        <div class="stat-card used">
                            <div class="stat-icon"><i data-lucide="check-circle-2"></i></div>
                            <div class="stat-info">
                                <h3>사용 완료</h3>
                                <p id="stat-used">0 개</p>
                            </div>
                        </div>
                        <div class="stat-card pending">
                            <div class="stat-icon"><i data-lucide="clock"></i></div>
                            <div class="stat-info">
                                <h3>사용 가능</h3>
                                <p id="stat-pending">0 개</p>
                            </div>
                        </div>
                    </div>
                    <div class="recent-list card">
                        <div class="card-header">
                            <h2>최근 발행 내역</h2>
                        </div>
                        <div class="table-wrapper">
                            <table id="recent-coupons">
                                <thead>
                                    <tr>
                                        <th>고객명</th>
                                        <th>이벤트명</th>
                                        <th>쿠폰번호</th>
                                        <th>상태</th>
                                        <th>발행일</th>
                                        <th>만료일</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic content -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Events View -->
                <section id="view-events" class="view">
                    <div class="card">
                        <div class="card-header flex">
                            <h2>이벤트 리스트</h2>
                            <button class="btn btn-primary" onclick="openNewEventModal()">새 이벤트 등록</button>
                        </div>
                        <div class="table-wrapper">
                            <table id="event-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>이벤트명</th>
                                        <th>할인 대상</th>
                                        <th>할인 설정</th>
                                        <th>유효일수</th>
                                        <th>조작</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Coupon List View (Full Screen) -->
                <section id="view-coupon-list" class="view">
                    <div class="card">
                        <div class="card-header flex">
                            <h2 id="coupon-list-title">쿠폰발행조회</h2>
                            <button class="btn" onclick="backToEvents()">◀ 돌아가기</button>
                        </div>
                        <div class="search-bar" style="margin-bottom: 1.5rem;">
                            <input type="text" id="coupon-list-search-full" placeholder="고객명 또는 연락처로 검색..."
                                onkeyup="searchCouponsInFullView()">
                        </div>
                        <div class="table-wrapper">
                            <table id="full-coupon-table">
                                <thead>
                                    <tr>
                                        <th>고객명</th>
                                        <th>연락처</th>
                                        <th>쿠폰번호</th>
                                        <th>상태</th>
                                        <th>발행일</th>
                                        <th>만료일</th>
                                        <th>사용일자</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Issue View -->
                <section id="view-issue" class="view">
                    <div class="card narrow">
                        <div class="card-header">
                            <h2>할인권 개별 발행하기</h2>
                        </div>
                        <form id="issue-form" class="form-container">
                            <div class="form-group">
                                <label>이벤트 선택</label>
                                <select id="issue-event-id" required></select>
                            </div>
                            <div class="form-group">
                                <label>고객 성함</label>
                                <input type="text" id="issue-customer-name" placeholder="고객명을 입력하세요" required>
                            </div>
                            <div class="form-group">
                                <label>연락처</label>
                                <input type="text" id="issue-phone" placeholder="010-0000-0000" required>
                            </div>
                            <button type="submit" class="btn btn-large">할인권 생성 및 메시지 준비</button>
                        </form>
                    </div>
                    <div id="issue-result" class="card narrow" style="display:none;">
                        <div class="result-message">
                            <div class="icon-success"><i data-lucide="check-circle-2"></i></div>
                            <h3>발행 완료!</h3>
                            <p>아래 문구를 복사하여 고객에게 발송하세요.</p>
                            <div class="copy-box">
                                <textarea id="copy-text" readonly></textarea>
                                <button onclick="copyToClipboard()" class="btn btn-copy">문구 복사하기</button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Verify View -->
                <section id="view-verify" class="view">
                    <div class="card narrow">
                        <div class="card-header">
                            <h2>쿠폰 조회 및 사용 처리</h2>
                        </div>
                        <div class="search-container">
                            <input type="text" id="verify-code" placeholder="쿠폰번호 입력 (예: TK123456)" maxlength="10">
                            <button onclick="verifyCoupon()" class="btn btn-primary">조회하기</button>
                        </div>
                    </div>
                    <div id="verify-result" class="card narrow" style="display:none;">
                        <!-- Content via JS -->
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="event-modal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2 id="modal-title">새 이벤트 등록</h2>
                <button class="close-btn" onclick="closeModal('event-modal')">&times;</button>
            </div>
            <form id="event-form">
                <input type="hidden" id="event-id">
                <div class="form-row" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>이벤트 명</label>
                        <input type="text" id="event-name" required placeholder="예: 렌탈 고객 전용 세탁 할인">
                    </div>
                    <div class="form-group">
                        <label>할인 대상</label>
                        <select id="event-target">
                            <option value="텐트세탁비 할인">텐트세탁비 할인</option>
                            <option value="우레탄창 할인">우레탄창 할인</option>
                            <option value="발수코팅 무료">발수코팅 무료</option>
                            <option value="곰팡이제거 무료">곰팡이제거 무료</option>
                            <option value="배송비 무료">배송비 무료</option>
                            <option value="기타">기타</option>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>할인 유형</label>
                        <select id="event-type">
                            <option value="FIXED">고정 금액(원)</option>
                            <option value="PERCENT">퍼센트(%)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>할인 수치</label>
                        <input type="number" id="event-value" value="5000" required>
                    </div>
                    <div class="form-group">
                        <label>유효 기간 (발행일로부터 일수)</label>
                        <input type="number" id="event-valid-days" value="30" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>상세 내용 (메모)</label>
                    <textarea id="event-desc" rows="2" placeholder="이벤트 상세 메모"></textarea>
                </div>
                <div class="form-group">
                    <label>안내 문구 템플릿 (고객 발송용)</label>
                    <textarea id="event-template" rows="8"></textarea>
                    <p class="help-text" style="font-size: 0.75rem; color: #636e72; margin-top: 0.3rem;">
                        치환자: [이벤트명], [할인대상], [할인수치], [할인유형], [유효기간], [쿠폰번호]
                    </p>
                </div>
                <button type="submit" class="btn btn-primary btn-large" id="event-submit-btn">저장하기</button>
            </form>
        </div>
    </div>


    <script src="script.js"></script>
    <script>lucide.createIcons();</script>
</body>

</html>