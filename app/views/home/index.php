<section class="page">
    <h1>WhosThatPokemon - Pokedle MVP</h1>

    <section class="auth-panel">
        <h2>Iniciar sesión</h2>
        <form id="login-form" class="login-form" autocomplete="off">
            <label for="login-identifier">Usuario o email</label>
            <input id="login-identifier" type="text" placeholder="usuario o correo">

            <label for="login-password">Contraseña</label>
            <input id="login-password" type="password" placeholder="contraseña">

            <button type="submit">Entrar</button>
        </form>

        <button id="logout-button" type="button" class="logout-button" hidden>Cerrar sesión</button>
        <p id="auth-status" class="auth-status"></p>
    </section>

    <p id="challenge-date">Cargando reto...</p>

    <form id="guess-form" autocomplete="off">
        <label for="pokemon-search">Tu intento</label>
        <input id="pokemon-search" type="search" placeholder="Buscar pokemon..." autocomplete="off">
        <ul id="pokemon-suggestions" class="pokemon-suggestions" hidden></ul>
        <button type="submit">Probar</button>
    </form>

    <div id="result" class="result"></div>
    <div id="hint" class="hint" hidden></div>

    <pre id="debug" class="debug"></pre>
</section>
