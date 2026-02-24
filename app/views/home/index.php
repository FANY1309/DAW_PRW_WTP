<section class="page">
    <h1>WhosThatPokemon - Pokedle MVP</h1>

    <section class="auth-panel">
        <div class="auth-actions">
            <button id="show-login-button" type="button" class="show-login-button">Iniciar sesión</button>
            <button id="show-register-button" type="button" class="show-register-button">Registrarse</button>
        </div>
        <div id="login-modal" class="login-modal" hidden>
            <div class="login-modal-card" role="dialog" aria-modal="true" aria-labelledby="login-title">
                <div class="login-modal-header">
                    <h2 id="login-title">Iniciar sesion</h2>
                    <button id="close-login-button" type="button" class="close-login-button" aria-label="Cerrar ventana de inicio de sesion">x</button>
                </div>
                <form id="login-form" class="login-form" autocomplete="off">
                    <label for="login-identifier">Usuario o email</label>
                    <input id="login-identifier" type="text" placeholder="usuario o correo">

                    <label for="login-password">Contrasena</label>
                    <input id="login-password" type="password" placeholder="contrasena">

                    <button type="submit">Entrar</button>
                </form>
            </div>
        </div>
        <div id="register-modal" class="login-modal" hidden>
            <div class="login-modal-card" role="dialog" aria-modal="true" aria-labelledby="register-title">
                <div class="login-modal-header">
                    <h2 id="register-title">Registrarse</h2>
                    <button id="close-register-button" type="button" class="close-login-button" aria-label="Cerrar ventana de registro">x</button>
                </div>
                <form id="register-form" class="login-form" autocomplete="off">
                    <label for="register-usuario">Usuario</label>
                    <input id="register-usuario" type="text" placeholder="tu usuario">

                    <label for="register-email">Email</label>
                    <input id="register-email" type="email" placeholder="tu@email.com">

                    <label for="register-nombre">Nombre</label>
                    <input id="register-nombre" type="text" placeholder="tu nombre">

                    <label for="register-password">Contraseña</label>
                    <input id="register-password" type="password" placeholder="mínimo 6 carácteres">

                    <button type="submit">Registrarme</button>
                </form>
            </div>
        </div>
        

        <button id="logout-button" type="button" class="logout-button" hidden>Cerrar sesión</button>
        <button id="show-ranking-button" type="button" class="show-ranking-button" hidden>Ver ranking global</button>
        <p id="auth-status" class="auth-status"></p>

        <section id="admin-sync-panel" class="admin-sync-panel" hidden>
            <h3>Administración Pokemon</h3>
            <form id="admin-sync-form" class="admin-sync-form" autocomplete="off">
                <label for="admin-generation-input">Generación (1-9)</label>
                <input id="admin-generation-input" type="number" min="1" max="9" step="1" value="1">
                <button id="admin-sync-button" type="submit">Actualizar lista desde PokeAPI</button>
            </form>
            <p id="admin-sync-status" class="admin-sync-status"></p>
        </section>
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

    <?php
    $appEnv = strtolower(trim((string)($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: 'production')));
    if ($appEnv !== 'production'):
    ?>
    <pre id="debug" class="debug"></pre>
    <?php endif; ?>
</section>

