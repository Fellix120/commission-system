<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Commission Dashboard')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/expert-logo2.png') }}">
  <link
    href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@400;500;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap"
    rel="stylesheet">
  <style>
    :root {
      --g-blue: #1a73e8;
      --g-blue-dark: #1967d2;
      --g-blue-light: #e8f0fe;
      --g-green: #188038;
      --g-green-light: #e6f4ea;
      --g-red: #d93025;
      --g-red-light: #fce8e6;
      --g-yellow: #f9ab00;
      --g-yellow-light: #fef7e0;
      --g-grey-900: #202124;
      --g-grey-700: #5f6368;
      --g-grey-500: #80868b;
      --g-grey-300: #dadce0;
      --g-grey-100: #f1f3f4;
      --g-grey-50: #f8f9fa;
      --g-white: #fff;
      --shadow-1: 0 1px 2px 0 rgba(60, 64, 67, .3), 0 1px 3px 1px rgba(60, 64, 67, .15);
      --shadow-2: 0 1px 3px 0 rgba(60, 64, 67, .3), 0 4px 8px 3px rgba(60, 64, 67, .15);
      --rail-w: 256px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    body {
      font-family: 'Google Sans', 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: var(--g-grey-50);
      color: var(--g-grey-900);
      font-size: 14px;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
    }

    .require {
      color: red;
    }

    .material-symbols-outlined {
      font-family: 'Material Symbols Outlined';
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      font-size: 20px;
      line-height: 1;
      user-select: none;
    }

    /* ---------- Top app bar ---------- */
    .topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 64px;
      z-index: 100;
      background: var(--g-white);
      border-bottom: 1px solid var(--g-grey-300);
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 0 16px;
    }

    .topbar__menu {
      border: 0;
      background: transparent;
      cursor: pointer;
      padding: 10px;
      border-radius: 50%;
      color: var(--g-grey-700);
      display: flex;
    }

    .topbar__menu:hover {
      background: var(--g-grey-100)
    }

    .topbar__brand {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-right: 16px
    }

    .topbar__logo {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: var(--g-blue);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 15px;
      flex-shrink: 0;
    }

    .topbar__title {
      font-size: 20px;
      color: var(--g-grey-700);
      white-space: nowrap
    }

    .topbar__search {
      flex: 1;
      max-width: 720px;
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--g-grey-100);
      border-radius: 8px;
      padding: 0 14px;
      height: 44px;
      transition: background .15s, box-shadow .15s;
    }

    .topbar__search:focus-within {
      background: var(--g-white);
      box-shadow: var(--shadow-1)
    }

    .topbar__search input {
      border: 0;
      background: transparent;
      outline: 0;
      flex: 1;
      font-size: 15px;
      font-family: inherit
    }

    .topbar__search .material-symbols-outlined {
      color: var(--g-grey-700)
    }

    .topbar__right {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 8px
    }

    .avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: var(--g-green);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 500;
    }

    /* ---------- Left nav rail ---------- */
    .rail {
      position: fixed;
      top: 64px;
      bottom: 0;
      left: 0;
      width: var(--rail-w);
      background: var(--g-grey-50);
      overflow-y: auto;
      padding: 8px 8px 24px;
      z-index: 90;
      transition: transform .2s ease;
    }

    .rail__cta {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 8px 8px 16px;
      background: var(--g-white);
      color: var(--g-grey-900);
      border: 0;
      border-radius: 16px;
      padding: 14px 22px 14px 16px;
      cursor: pointer;
      font-family: inherit;
      font-size: 14px;
      font-weight: 500;
      box-shadow: var(--shadow-1);
      text-decoration: none;
      width: calc(100% - 16px);
    }

    .rail__cta:hover {
      box-shadow: var(--shadow-2);
      background: #fafafb
    }

    .rail__cta .material-symbols-outlined {
      color: var(--g-blue);
      font-size: 22px
    }

    .rail__section {
      font-size: 11px;
      font-weight: 500;
      color: var(--g-grey-500);
      text-transform: uppercase;
      letter-spacing: .8px;
      padding: 16px 12px 6px;
    }

    .rail__link {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 0 12px;
      height: 40px;
      border-radius: 0 20px 20px 0;
      color: var(--g-grey-900);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 2px;
    }

    .rail__link:hover {
      background: var(--g-grey-100)
    }

    .rail__link.is-active {
      background: var(--g-blue-light);
      color: var(--g-blue-dark)
    }

    .rail__link.is-active .material-symbols-outlined {
      color: var(--g-blue-dark)
    }

    .rail__link .material-symbols-outlined {
      color: var(--g-grey-700);
      flex-shrink: 0
    }

    .rail__count {
      margin-left: auto;
      font-size: 12px;
      color: var(--g-grey-700);
      font-weight: 400;
    }

    /* ---------- Main ---------- */
    .main {
      margin: 64px 0 0 var(--rail-w);
      padding: 24px;
      min-height: calc(100vh - 64px)
    }

    .page-head {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 20px;
      flex-wrap: wrap
    }

    .page-head h1 {
      font-size: 22px;
      font-weight: 400;
      color: var(--g-grey-900)
    }

    .page-head__actions {
      margin-left: auto;
      display: flex;
      gap: 8px;
      flex-wrap: wrap
    }

    /* ---------- Buttons ---------- */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      height: 36px;
      padding: 0 16px;
      border-radius: 18px;
      border: 1px solid transparent;
      cursor: pointer;
      font-family: inherit;
      font-size: 14px;
      font-weight: 500;
      text-decoration: none;
      white-space: nowrap;
      background: transparent;
      color: var(--g-blue);
    }

    .btn .material-symbols-outlined {
      font-size: 18px
    }

    .btn--filled {
      background: var(--g-blue);
      color: #fff
    }

    .btn--filled:hover {
      background: var(--g-blue-dark);
      box-shadow: var(--shadow-1)
    }

    .btn--tonal {
      background: var(--g-blue-light);
      color: var(--g-blue-dark)
    }

    .btn--tonal:hover {
      background: #d2e3fc
    }

    .btn--outlined {
      border-color: var(--g-grey-300);
      color: var(--g-blue);
      background: var(--g-white)
    }

    .btn--outlined:hover {
      background: var(--g-blue-light);
      border-color: var(--g-blue-light)
    }

    .btn--text:hover {
      background: var(--g-grey-100)
    }

    .btn--danger {
      color: var(--g-red)
    }

    .btn--danger:hover {
      background: var(--g-red-light)
    }

    .btn--sm {
      height: 30px;
      padding: 0 12px;
      font-size: 13px;
      border-radius: 15px
    }

    .btn:disabled {
      opacity: .5;
      cursor: not-allowed
    }

    /* ---------- Cards ---------- */
    .card {
      background: var(--g-white);
      border: 1px solid var(--g-grey-300);
      border-radius: 8px;
      overflow: hidden;
    }

    .card--pad {
      padding: 20px
    }

    .card__head {
      padding: 16px 20px;
      border-bottom: 1px solid var(--g-grey-300);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .card__title {
      font-size: 16px;
      font-weight: 500
    }

    .card__sub {
      font-size: 12px;
      color: var(--g-grey-700)
    }

    /* ---------- Stat tiles ---------- */
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 12px;
      margin-bottom: 20px
    }

    .stat {
      background: var(--g-white);
      border: 1px solid var(--g-grey-300);
      border-radius: 12px;
      padding: 16px 18px;
    }

    .stat__label {
      font-size: 12px;
      color: var(--g-grey-700);
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 8px;
    }

    .stat__label .material-symbols-outlined {
      font-size: 16px
    }

    .stat__value {
      font-size: 26px;
      font-weight: 400;
      letter-spacing: -.5px
    }

    .stat__hint {
      font-size: 11px;
      color: var(--g-grey-500);
      margin-top: 4px
    }

    .stat--blue .stat__value {
      color: var(--g-blue)
    }

    .stat--green .stat__value {
      color: var(--g-green)
    }

    .stat--red .stat__value {
      color: var(--g-red)
    }

    .stat--yellow .stat__value {
      color: #b06000
    }

    /* ---------- Filter chips ---------- */
    .chips {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
      margin-bottom: 16px
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      height: 32px;
      padding: 0 12px;
      border: 1px solid var(--g-grey-300);
      border-radius: 8px;
      background: var(--g-white);
      font-size: 13px;
      font-weight: 500;
      color: var(--g-grey-700);
      cursor: pointer;
      text-decoration: none;
      font-family: inherit;
    }

    .chip:hover {
      background: var(--g-grey-100)
    }

    .chip.is-active {
      background: var(--g-blue-light);
      border-color: var(--g-blue-light);
      color: var(--g-blue-dark)
    }

    .chip .material-symbols-outlined {
      font-size: 16px
    }

    .chip select {
      border: 0;
      background: transparent;
      outline: 0;
      font-family: inherit;
      font-size: 13px;
      font-weight: 500;
      color: inherit;
      cursor: pointer
    }

    /* ---------- Tables (Sheets-like) ---------- */
    .table-wrap {
      overflow: auto;
      max-height: calc(100vh - 300px);
      border-radius: 8px
    }

    table {
      border-collapse: separate;
      border-spacing: 0;
      width: 100%;
      font-size: 13px;
      background: var(--g-white)
    }

    thead th {
      position: sticky;
      top: 0;
      z-index: 5;
      background: var(--g-grey-100);
      font-weight: 500;
      color: var(--g-grey-700);
      text-align: left;
      padding: 10px 12px;
      white-space: nowrap;
      border-bottom: 1px solid var(--g-grey-300);
      font-size: 12px;
    }

    thead th.is-num {
      text-align: right
    }

    tbody td {
      padding: 10px 12px;
      border-bottom: 1px solid var(--g-grey-100);
      white-space: nowrap;
      color: var(--g-grey-900);
    }

    tbody tr:hover {
      background: var(--g-grey-50)
    }

    tbody tr.is-archived {
      background: var(--g-grey-50);
      color: var(--g-grey-500)
    }

    td.is-num {
      text-align: right;
      font-variant-numeric: tabular-nums
    }

    td.is-money {
      text-align: right;
      font-variant-numeric: tabular-nums
    }

    .col-sticky {
      position: sticky;
      left: 0;
      background: var(--g-white);
      z-index: 4;
      border-right: 1px solid var(--g-grey-300);
    }

    thead .col-sticky {
      z-index: 6;
      background: var(--g-grey-100)
    }

    tbody tr:hover .col-sticky {
      background: var(--g-grey-50)
    }

    .cell-truncate {
      max-width: 260px;
      overflow: hidden;
      text-overflow: ellipsis;
      display: inline-block;
      vertical-align: bottom
    }

    /* intake commission columns get a tint so they read as a group */
    .col-intake {
      background: #fef7e0
    }

    thead th.col-intake {
      background: #fdecc0
    }

    tbody tr:hover .col-intake {
      background: #fdf3d4
    }

    /* ---------- Badges ---------- */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      height: 22px;
      padding: 0 8px;
      border-radius: 11px;
      font-size: 11px;
      font-weight: 500;
    }

    .badge--green {
      background: var(--g-green-light);
      color: var(--g-green)
    }

    .badge--red {
      background: var(--g-red-light);
      color: var(--g-red)
    }

    .badge--yellow {
      background: var(--g-yellow-light);
      color: #b06000
    }

    .badge--blue {
      background: var(--g-blue-light);
      color: var(--g-blue-dark)
    }

    .badge--grey {
      background: var(--g-grey-100);
      color: var(--g-grey-700)
    }

    /* ---------- Forms ---------- */
    .field {
      margin-bottom: 16px
    }

    .field label {
      display: block;
      font-size: 12px;
      color: var(--g-grey-700);
      margin-bottom: 6px;
      font-weight: 500
    }

    .field input,
    .field select,
    .field textarea {
      width: 100%;
      height: 40px;
      padding: 0 12px;
      border: 1px solid var(--g-grey-300);
      border-radius: 4px;
      font-family: inherit;
      font-size: 14px;
      outline: 0;
      background: var(--g-white);
    }

    .field textarea {
      height: auto;
      padding: 10px 12px;
      resize: vertical;
      min-height: 72px
    }

    .field input:focus,
    .field select:focus,
    .field textarea:focus {
      border-color: var(--g-blue);
      border-width: 2px;
      padding: 0 11px
    }

    .field textarea:focus {
      padding: 9px 11px
    }

    .field__hint {
      font-size: 11px;
      color: var(--g-grey-500);
      margin-top: 4px
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 0 16px
    }

    /* ---------- Snackbar ---------- */
    .snackbar {
      position: fixed;
      bottom: 24px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--g-grey-900);
      color: #fff;
      padding: 14px 20px;
      border-radius: 8px;
      box-shadow: var(--shadow-2);
      z-index: 200;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 16px;
      max-width: 640px;
    }

    .snackbar--error {
      background: var(--g-red)
    }

    .snackbar button {
      background: transparent;
      border: 0;
      color: #8ab4f8;
      font-weight: 500;
      cursor: pointer;
      font-family: inherit
    }

    .snackbar--error button {
      color: #fff
    }

    /* ---------- Empty state ---------- */
    .empty {
      text-align: center;
      padding: 64px 24px;
      color: var(--g-grey-700)
    }

    .empty .material-symbols-outlined {
      font-size: 48px;
      color: var(--g-grey-300);
      margin-bottom: 12px
    }

    .empty h3 {
      font-weight: 400;
      font-size: 18px;
      margin-bottom: 6px;
      color: var(--g-grey-900)
    }

    /* ---------- Pagination ---------- */
    .pager {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 4px;
      font-size: 13px;
      color: var(--g-grey-700)
    }

    .pager nav {
      display: flex;
      gap: 4px
    }

    .pager a,
    .pager span {
      padding: 6px 10px;
      border-radius: 4px;
      text-decoration: none;
      color: var(--g-grey-700);
      font-size: 13px;
    }

    .pager a:hover {
      background: var(--g-grey-100)
    }

    .pager .active span {
      background: var(--g-blue-light);
      color: var(--g-blue-dark);
      font-weight: 500
    }

    .pager .is-current {
      background: var(--g-blue-light);
      color: var(--g-blue-dark);
      font-weight: 500
    }

    .pager .is-disabled {
      color: var(--g-grey-300);
      cursor: default
    }

    .pager .is-dots {
      color: var(--g-grey-500);
      padding: 6px 4px
    }

    .pager nav .material-symbols-outlined {
      font-size: 18px;
      vertical-align: middle
    }

    .page-actions {
      display: flex;
      gap: 8px;
      align-items: center
    }

    .row-actions {
      white-space: nowrap
    }

    .row-actions .btn {
      padding: 4px 6px;
      min-width: 0
    }

    /* ---------- Progress bar (targets) ---------- */
    .progress {
      height: 8px;
      background: var(--g-grey-100);
      border-radius: 4px;
      overflow: hidden;
      min-width: 120px
    }

    .progress__fill {
      height: 100%;
      border-radius: 4px;
      background: var(--g-blue);
      transition: width .3s
    }

    .progress__fill--green {
      background: var(--g-green)
    }

    .progress__fill--yellow {
      background: var(--g-yellow)
    }

    .progress__fill--red {
      background: var(--g-red)
    }

    /* ---------- Details drawer ---------- */
    .detail-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 16px
    }

    .kv {
      padding: 12px 0;
      border-bottom: 1px solid var(--g-grey-100)
    }

    .kv__k {
      font-size: 11px;
      color: var(--g-grey-500);
      text-transform: uppercase;
      letter-spacing: .5px
    }

    .kv__v {
      font-size: 14px;
      margin-top: 2px
    }


    .user-menu {
      position: relative;
    }

    .user-menu__button {
      border: 0;
      cursor: pointer;
      font-family: inherit;
    }

    .user-menu__dropdown {
      display: none;
      position: absolute;
      right: 0;
      top: calc(100% + 10px);
      width: 280px;
      background: #fff;
      border: 1px solid var(--g-grey-300);
      border-radius: 14px;
      box-shadow: var(--shadow-2);
      padding: 10px;
      z-index: 1200;
    }

    .user-menu.is-open .user-menu__dropdown {
      display: block;
    }

    .user-menu__profile {
      display: flex;
      gap: 12px;
      align-items: center;
      padding: 10px;
      border-bottom: 1px solid var(--g-grey-100);
      margin-bottom: 6px;
    }

    .user-menu__profile strong,
    .user-menu__profile span {
      display: block;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      max-width: 185px;
    }

    .user-menu__profile span {
      color: var(--g-grey-700);
      font-size: 12px;
      margin-top: 2px;
    }

    .avatar--large {
      width: 42px;
      height: 42px;
      flex: 0 0 42px;
    }

    .user-menu__dropdown a,
    .user-menu__dropdown button {
      width: 100%;
      display: flex;
      align-items: center;
      gap: 10px;
      border: 0;
      background: transparent;
      color: var(--g-grey-900);
      text-decoration: none;
      padding: 10px;
      border-radius: 8px;
      cursor: pointer;
      font: inherit;
      text-align: left;
    }

    .user-menu__dropdown a:hover,
    .user-menu__dropdown button:hover {
      background: var(--g-grey-100);
    }

    .user-menu__dropdown .material-symbols-outlined {
      font-size: 20px;
      color: var(--g-grey-700);
    }

    .account-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .account-card {
      padding: 22px;
    }

    .account-form {
      margin-top: 20px;
    }

    .form-field {
      margin-bottom: 17px;
    }

    .form-field label {
      display: block;
      margin-bottom: 7px;
      font-weight: 500;
    }

    .form-field input {
      width: 100%;
      height: 43px;
      border: 1px solid var(--g-grey-300);
      border-radius: 8px;
      padding: 0 12px;
      font: inherit;
      outline: none;
    }

    .form-field input:focus {
      border-color: var(--g-blue);
      box-shadow: 0 0 0 3px rgba(26, 115, 232, .12);
    }

    .field-error {
      margin-top: 5px;
      color: var(--g-red);
      font-size: 12px;
    }

    @media (max-width: 800px) {
      .account-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width:960px) {
      .rail {
        transform: translateX(-100%)
      }

      .rail.is-open {
        transform: none;
        box-shadow: var(--shadow-2);
        background: var(--g-white)
      }

      .main {
        margin-left: 0
      }

      .topbar__title {
        display: none
      }
    }
  </style>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
  <style>
    .chip-input {
      border: 0;
      outline: 0;
      background: transparent;
      font: inherit;
      width: 170px
    }

    .choices {
      margin: 0;
      min-width: 150px
    }

    .choices__inner {
      min-height: 28px !important;
      padding: 2px 24px 2px 4px !important;
      border: 0 !important;
      background: transparent !important;
      font-size: 13px !important
    }

    .choices__list--single {
      padding: 3px 16px 3px 4px !important
    }

    .choices__list--dropdown {
      z-index: 1000;
      min-width: 240px
    }

    .choices[data-type*=select-one]::after {
      right: 8px
    }

    .choices__list--dropdown,
    .choices__list[aria-expanded] {
      display: none;
      z-index: 999;
      position: absolute;
      width: var(--choices-width, 100%);
      background-color: var(--choices-bg-color-dropdown, #fff);
      border: var(--choices-base-border, 1px solid) var(--choices-keyline-color, #ddd);
      top: 100%;
      margin-top: -1px;
      border-bottom-left-radius: var(--choices-border-radius, 2.5px);
      border-bottom-right-radius: var(--choices-border-radius, 2.5px);
      overflow: hidden;
      word-break: break-all;
    }

    /* SweetAlert2 – match the app's type and button shapes */
    .swal2-popup {
      font-family: 'Google Sans', 'Roboto', sans-serif !important;
      border-radius: 14px !important;
      font-size: 14px !important;
    }

    .swal2-title {
      font-size: 20px !important;
      font-weight: 500 !important;
      color: var(--g-grey-900) !important;
    }

    .swal2-html-container {
      color: var(--g-grey-700) !important;
      font-size: 14px !important;
      line-height: 1.6 !important;
    }

    .swal2-styled {
      border-radius: 18px !important;
      font-weight: 500 !important;
      font-size: 14px !important;
      padding: 8px 20px !important;
      box-shadow: none !important;
    }
  </style>
</head>

<body>

  <header class="topbar">
    <button class="topbar__menu" onclick="document.querySelector('.rail').classList.toggle('is-open')"
      aria-label="Menu">
      <span class="material-symbols-outlined">menu</span>
    </button>
    <div class="topbar__brand">
      <div class="topbar__logo">CD</div>
      <span class="topbar__title">Commission Dashboard</span>
    </div>

    <form class="topbar__search" method="GET" action="{{ route('students.index') }}">
      <span class="material-symbols-outlined">search</span>
      <input type="text" name="search" placeholder="Search students, client ID, course" value="{{ request('search') }}"
        aria-label="Search">
    </form>

    <div class="topbar__right">
      <a href="{{ route('import.index') }}" class="btn btn--text" title="Import workbook">
        <span class="material-symbols-outlined">upload_file</span>
      </a>
      <div class="user-menu">
        <button type="button" class="avatar user-menu__button" onclick="this.parentElement.classList.toggle('is-open')"
          aria-label="Account menu">
          {{ auth()->user()->initials() }}
        </button>
        <div class="user-menu__dropdown">
          <div class="user-menu__profile">
            <div class="avatar avatar--large">{{ auth()->user()->initials() }}</div>
            <div>
              <strong>{{ auth()->user()->name }}</strong>
              <span>{{ auth()->user()->email }}</span>
            </div>
          </div>
          <a href="{{ route('account.edit') }}"><span class="material-symbols-outlined">manage_accounts</span> My
            account</a>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"><span class="material-symbols-outlined">logout</span> Logout</button>
          </form>
        </div>
      </div>
    </div>
  </header>

  <nav class="rail">
    <a href="{{ route('import.index') }}" class="rail__cta">
      <span class="material-symbols-outlined">add</span> Import workbook
    </a>

    <a href="{{ route('dashboard') }}" class="rail__link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
      <span class="material-symbols-outlined">dashboard</span> Dashboard
    </a>

    <div class="rail__section">Display</div>
    <a href="{{ route('students.index') }}"
      class="rail__link {{ request()->routeIs('students.*') && !request()->boolean('archived') ? 'is-active' : '' }}">
      <span class="material-symbols-outlined">groups</span> Student Details
      <span class="rail__count">{{ \App\Models\Student::active()->count() }}</span>
    </a>
    <a href="{{ route('students.index', ['archived' => 1]) }}"
      class="rail__link {{ request()->boolean('archived') ? 'is-active' : '' }}">
      <span class="material-symbols-outlined">inventory_2</span> Archived
      <span class="rail__count">{{ \App\Models\Student::archived()->count() }}</span>
    </a>
    <a href="{{ route('targets.index') }}" class="rail__link {{ request()->routeIs('targets.*') ? 'is-active' : '' }}">
      <span class="material-symbols-outlined">track_changes</span> Targets
    </a>

    <div class="rail__section">Source tabs</div>
    <a href="{{ route('formula.index') }}" class="rail__link {{ request()->routeIs('formula.*') ? 'is-active' : '' }}">
      <span class="material-symbols-outlined">function</span> Student Details-Formula
    </a>
    <a href="{{ route('invoices.index') }}"
      class="rail__link {{ request()->routeIs('invoices.*') ? 'is-active' : '' }}">
      <span class="material-symbols-outlined">receipt_long</span> Invoice
    </a>
    <a href="{{ route('crm.index') }}" class="rail__link {{ request()->routeIs('crm.index') ? 'is-active' : '' }}">
      <span class="material-symbols-outlined">table_chart</span> CRM Data
    </a>
    <a href="{{ route('crm.raw') }}" class="rail__link {{ request()->routeIs('crm.raw') ? 'is-active' : '' }}">
      <span class="material-symbols-outlined">database</span> Raw Data Export
    </a>

    <div class="rail__section">Account</div>
    <a href="{{ route('account.edit') }}" class="rail__link {{ request()->routeIs('account.*') ? 'is-active' : '' }}">
      <span class="material-symbols-outlined">manage_accounts</span> My Account
    </a>

    <div class="rail__section">Tools</div>
    <a href="{{ route('import.index') }}" class="rail__link {{ request()->routeIs('import.*') ? 'is-active' : '' }}">
      <span class="material-symbols-outlined">history</span> Import history
    </a>
    {{-- <a href="{{ route('export.students') }}" class="rail__link">
      <span class="material-symbols-outlined">download</span> Export CSV
    </a> --}}
  </nav>

  <main class="main">
    @yield('content')
  </main>

  @if (session('status'))
    <div class="snackbar" id="snack">
      <span>{{ session('status') }}</span>
      <button onclick="document.getElementById('snack').remove()">Dismiss</button>
    </div>
  @endif
  @if (session('error'))
    <div class="snackbar snackbar--error" id="snack-err">
      <span>{{ session('error') }}</span>
      <button onclick="document.getElementById('snack-err').remove()">Dismiss</button>
    </div>
  @endif

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    setTimeout(() => document.querySelectorAll('.snackbar').forEach(s => s.remove()), 6000);

    document.addEventListener('click', function (e) {
      document.querySelectorAll('.user-menu.is-open').forEach(function (menu) {
        if (!menu.contains(e.target)) menu.classList.remove('is-open');
      });
    });

    // One SweetAlert2 dialog for every confirmation in the app. Falls back to
    // window.confirm if the CDN script did not load, and returns a promise
    // either way so callers can share one code path.
    function appConfirm(opts) {
      if (typeof Swal === 'undefined') {
        return Promise.resolve(window.confirm(opts.text));
      }
      return Swal.fire({
        title: opts.title || 'Are you sure?',
        text: opts.text,
        icon: opts.danger ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText: opts.button || 'Yes, continue',
        cancelButtonText: 'Cancel',
        confirmButtonColor: opts.danger ? '#d93025' : '#1a73e8',
        reverseButtons: true,
        focusCancel: !!opts.danger
      }).then(result => result.isConfirmed);
    }

    // Any form carrying data-confirm asks before it submits. Delegated, so it
    // covers rows rendered on every page without per-view wiring.
    //
    // The dialog is async, so the flow is: block the submit, ask, then call
    // form.submit() on confirm -- which does not re-fire the submit event, so
    // there is no loop. Optional attributes: data-confirm-title and
    // data-confirm-button. Forms whose button carries btn--danger get the
    // warning icon and a red confirm button automatically.
    document.addEventListener('submit', function (e) {
      const form = e.target.closest('form[data-confirm]');
      if (!form) return;

      e.preventDefault();

      appConfirm({
        title: form.dataset.confirmTitle,
        text: form.dataset.confirm,
        button: form.dataset.confirmButton,
        danger: !!form.querySelector('.btn--danger')
      }).then(ok => {
        if (ok) form.submit();
      });
    });

    // "Select all" checkbox in a table header drives every row checkbox and
    // keeps the bulk action buttons enabled only when something is ticked.
    document.addEventListener('change', function (e) {
      const table = e.target.closest('[data-bulk]');
      if (!table) return;

      const boxes = table.querySelectorAll('input[name="ids[]"]');

      if (e.target.matches('[data-bulk-all]')) {
        boxes.forEach(b => { b.checked = e.target.checked; });
      }

      const checked = table.querySelectorAll('input[name="ids[]"]:checked').length;
      const all = table.querySelector('[data-bulk-all]');
      if (all && !e.target.matches('[data-bulk-all]')) {
        all.checked = checked > 0 && checked === boxes.length;
        all.indeterminate = checked > 0 && checked < boxes.length;
      }

      table.querySelectorAll('[data-bulk-action]').forEach(btn => {
        btn.disabled = checked === 0;
      });
      const label = table.querySelector('[data-bulk-count]');
      if (label) label.textContent = checked ? checked + ' selected' : '';
    });

    // Bulk buttons post the selected ids to whichever action they name.
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('[data-bulk-action]');
      if (!btn) return;
      const table = btn.closest('[data-bulk]');
      const form = table.querySelector('form[data-bulk-form]');
      if (!form) return;

      const go = () => {
        form.action = btn.dataset.bulkAction;
        form.submit();
      };

      if (!btn.dataset.confirm) return go();

      appConfirm({
        title: btn.dataset.confirmTitle,
        text: btn.dataset.confirm,
        button: btn.dataset.confirmButton,
        danger: btn.classList.contains('btn--danger')
      }).then(ok => {
        if (ok) go();
      });
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
  <script>
    document.querySelectorAll('.searchable-select').forEach(function (el) {
      if (!el.dataset.choicesReady) { new Choices(el, { searchEnabled: true, shouldSort: false, itemSelectText: '', allowHTML: false }); el.dataset.choicesReady = '1'; }
    });
  </script>
  @stack('scripts')
</body>

</html>