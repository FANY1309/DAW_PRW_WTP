<section class="page">
    <h1>WhosThatPokemon - Pokedle MVP</h1>
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
