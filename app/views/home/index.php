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
        <button id="show-ranking-button" type="button" class="show-ranking-button" hidden>Ver ranking global</button>
        <p id="auth-status" class="auth-status"></p>
    </section>

    <div id="ranking-modal" class="ranking-modal" hidden>
        <div class="ranking-modal-card" role="dialog" aria-modal="true" aria-labelledby="ranking-title">
            <div class="ranking-modal-header">
                <h2 id="ranking-title">Ranking global</h2>
                <button id="close-ranking-button" type="button" class="close-ranking-button" aria-label="Cerrar ranking">x</button>
            </div>
            <p id="ranking-me" class="ranking-me"></p>
            <div class="ranking-table-wrap">
                <table class="ranking-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Jugador</th>
                            <th>Puntos</th>
                            <th>Retos</th>
                        </tr>
                    </thead>
                    <tbody id="ranking-body"></tbody>
                </table>
            </div>
        </div>
    </div>

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
