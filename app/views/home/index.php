<section class="page">
    <h1>WhosThatPokemon - Pokedle MVP</h1>

    <section class="auth-panel">
        <button id="show-login-button" type="button" class="show-login-button">Iniciar sesión</button>
        <div id="login-modal" class="login-modal" hidden>
            <div class="login-modal-card" role="dialog" aria-modal="true" aria-labelledby="login-title">
                <div class="login-modal-header">
                    <h2 id="login-title">Iniciar sesión</h2>
                    <button id="close-login-button" type="button" class="close-login-button" aria-label="Cerrar ventana de inicio de sesión">x</button>
                </div>
                <form id="login-form" class="login-form" autocomplete="off">
                    <label for="login-identifier">Usuario o email</label>
                    <input id="login-identifier" type="text" placeholder="usuario o correo">

                    <label for="login-password">Contraseña</label>
                    <input id="login-password" type="password" placeholder="contraseña">

                    <button type="submit">Entrar</button>
                </form>
            </div>
        </div>

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
    <div id="points" class="points" hidden></div>
    <div id="hint" class="hint" hidden></div>

    <pre id="debug" class="debug"></pre>
</section>
