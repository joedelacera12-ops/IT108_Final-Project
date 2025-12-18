<?php
// Role-aware dashboard sidebar. Detects current user role and renders role-specific links
// Usage: include __DIR__ . '/dashboard_sidebar.php' from any dashboard page.
require_once __DIR__ . '/auth.php';

$user = current_user();
$role = $user['role'] ?? null;
$uri = $_SERVER['REQUEST_URI'] ?? '';
$currentTab = $_GET['tab'] ?? null;

// helper to mark active links. If $part starts with 'tab:' we compare against the current ?tab value.
function ds_active($uri, $part) {
  // access current tab when used
  $currentTab = $_GET['tab'] ?? null;
  if (strpos($part, 'tab:') === 0) {
    $want = substr($part, strlen('tab:'));
    return ($currentTab === $want) ? ' active' : '';
  }
  return (strpos($uri, $part) !== false) ? ' active' : '';
}

// role color classes
$roleClass = 'dash-default';
if ($role === 'admin') $roleClass = 'dash-admin';
if ($role === 'seller') $roleClass = 'dash-seller';
if ($role === 'buyer') $roleClass = 'dash-buyer';
if ($role === 'delivery_partner') $roleClass = 'dash-delivery';
?>