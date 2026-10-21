@can('core.users.force-delete')
    <form class="d-inline" method="POST" action="{{ route('admin.users.force-delete', $user) }}" onsubmit="return confirm('Confirma a eliminação definitiva deste utilizador? Esta ação não pode ser anulada.')">
        @csrf
        @method('DELETE')
        <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
    </form>
@endcan
