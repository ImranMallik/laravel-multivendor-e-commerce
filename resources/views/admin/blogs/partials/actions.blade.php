{{-- Edit / Preview (signed storefront link, new tab) / Delete. Expects: $blog. --}}
<x-admin.button :href="route('admin.blogs.edit', $blog)" variant="primary" icon="fas fa-edit" size="sm" tooltip="Edit" />
<x-admin.button :href="route('admin.blogs.preview', $blog)" variant="info" icon="fas fa-eye" size="sm" tooltip="Preview" target="_blank" rel="noopener" />
<x-admin.button variant="danger" icon="fas fa-trash" size="sm" tooltip="Delete"
                data-delete-row
                data-url="{{ route('admin.blogs.destroy', $blog) }}"
                data-title="{{ $blog->title }}"
                data-noun="post" />
