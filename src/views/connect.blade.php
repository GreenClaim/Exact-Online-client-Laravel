<form method="post" action="{{ route('exact-online.authorize') }}">
    {{ csrf_field() }}
    <button class="button small orange" type="submit">Connect with Exact Online</button>
</form>
