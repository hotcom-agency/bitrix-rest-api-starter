<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<style>
  a.adm-header-btn.adm-header-btn-site {
    display: none;
  }

  .adm-sub-submenu-block:has(> .adm-submenu-item-name > a[href*="fileman_admin.php"]),
  a[href*="fileman_admin.php"][href*="site="] {
    display: none !important;
  }
</style>
