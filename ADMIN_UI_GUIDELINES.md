# Admin UI guidelines

The admin area (`resources/views/admin`, `public/admin-assets`) is built on the Stisla template in
`/template/admin-template`. Keep it visually and structurally consistent by following these rules.

## The rule

Every button, badge, card, form control, alert, modal, table, pagination, dropdown and icon uses the
**admin template's own classes**. No plain Bootstrap defaults that the template restyles, no Tailwind,
no invented class names, **no inline `style=""` attributes**.

- Look the markup up in `/template/admin-template/*.html` (e.g. `bootstrap-card.html`,
  `bootstrap-badge.html`, `bootstrap-form.html`, `modules-datatables.html`) and copy its classes.
- Icons: Font Awesome via the template (`fas`, `far`, `fab`).
- If something truly is missing from the template, add the smallest possible rule to
  `public/admin-assets/css/custom.css` (the template's designated override file) with a descriptive
  class name. Never use `style=""`, and never edit the template's `style.css` or `components.css`.

## Use the components

Build views from `resources/views/components/admin/` instead of writing the markup by hand:

| Component | Purpose |
|---|---|
| `<x-admin.button>` | `btn btn-*`; `href` renders `<a>`; `icon`, `size`, `block`, `outline`, `tooltip` |
| `<x-admin.badge>` | `badge badge-*`; pass `:status="$enum"` (reads `color()` / `label()`) |
| `<x-admin.card>` | `card` with `title`, `variant`, `actions` and `footer` slots |
| `<x-admin.page-header>` | title, breadcrumb and action buttons (the layout renders it from `@section('page-title')` and `@section('page-actions')`) |
| `<x-admin.alert>` | static in-page notices |
| `<x-admin.switch>` | the template's toggle (`custom-switch`); in a form it posts 0/1, in a table pass `data-url` for AJAX toggles |
| `<x-admin.form.input>` / `select` / `textarea` / `file` | `form-group` + `form-control` with the template's invalid state and error message |

Form components show validation errors under the field. Use `error-bag="name"` for named error bags.

## Statuses and badges

Colours and labels live **on the enum**, not in views or JS:

```php
enum VendorStatus: string {
    public function label(): string { ... }
    public function color(): string { ... }   // success | warning | danger | secondary ...
}
```

New status-like enums must provide `label()` and `color()` so `<x-admin.badge :status="...">` works.
Row/page actions follow the same idea (`VendorAction`, `VendorStatus::availableActions()`): the server
decides which buttons exist and sends the confirm copy as `data-*`; JS never hard-codes it.

## Confirmations, alerts and AJAX

- One centered SweetAlert2 for every confirmation and flash message. Use `data-confirm` on a form,
  button or link (see `public/admin-assets/js/admin-confirm.js`). Never `confirm()`, bootbox, the
  template's `fireModal` confirm, or a second library.
- Flash messages (`success`, `error`, ...) are shown by `layouts/partials/alerts.blade.php`.
- AJAX uses jQuery with the CSRF header already set up in `admin-ajax.js`. Endpoints return JSON
  (`{"message": ...}`, 422 with `errors` on validation failure) and reuse the same Action classes as
  the non-AJAX routes.

## Tables

Lists use server-side DataTables (yajra) with the template's bundled DataTables and Bootstrap 4 skin,
loaded once from the head/scripts partials. Filters are validated by a Form Request, eager-load relations,
and reload the table through AJAX (see `App\DataTables\VendorDataTable` and `vendors-table.js`).
Do not set `language.processing`: the template already styles `.dataTables_processing` with its own spinner,
so extra markup there shows a second one.

## Scripts and styles

- No inline `<style>` or `style=""`. Page scripts go in `public/admin-assets/js/` and are included with
  `@push('scripts')`; pass data to them with `data-*` attributes.
- Admin pages never load `frontend-assets`, and storefront pages never load `admin-assets`.

## Checklist for a new admin page

1. `@extends('admin.layouts.app')`, set `@section('page-title')` (and `page-actions` if needed).
2. Build the page from `<x-admin.*>` components.
3. Statuses via `<x-admin.badge :status>`; confirmations via `data-confirm`.
4. No `style=""`, no new CSS unless it is in `custom.css` and unavoidable.
5. Add a feature test for the page and for any JSON endpoint.
