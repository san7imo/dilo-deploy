# Catalogo publico de canciones

Este documento registra el primer hito para construir la experiencia publica de canciones de Dilo Records.

## Alcance de este hito

- No toca royalties, splits, payouts ni composiciones.
- No modifica servicios administrativos de tracks o releases.
- Crea una capa publica reusable para exponer canciones con artista, release, portada, plataformas y URL de embed de Spotify.
- `/artistas` usa esta capa para mostrar un resumen de maximo 5 canciones por artista.
- `/canciones` queda disponible como pagina publica base del catalogo completo.

## Contrato de cancion publica

Cada cancion expuesta por `App\Services\PublicCatalog\PublicTrackPresenter` incluye:

- `id`
- `title`
- `track_number`
- `duration`
- `isrc`
- `cover_url`
- `effective_cover_url`
- `optimized_cover_url`
- `spotify_url`
- `spotify_embed_url`
- `platforms`
- `release`
- `artists`

`platforms` contiene objetos con:

- `key`
- `name`
- `url`
- `icon`

## Spotify

La fuente de verdad local sigue siendo `tracks.spotify_url`.

El resolver acepta:

- `https://open.spotify.com/track/{id}`
- `https://open.spotify.com/intl-{locale}/track/{id}`
- `https://open.spotify.com/embed/track/{id}`
- `spotify:track:{id}`

Tambien soporta otros tipos que Spotify permite embeber, como `album`, `artist`, `episode`, `playlist` y `show`, aunque para canciones el tipo esperado es `track`.

No se convierte `spotify.link` en este hito porque requiere resolver la URL externamente u operar con oEmbed. La opcion reversible es conservar el link como plataforma y no generar `spotify_embed_url` hasta tener una resolucion auditada.

## Regla de reproductor Spotify

El componente `SpotifyEmbedPlayer` carga el iFrame API oficial de Spotify para crear el embed, cargar la URL de la cancion y llamar `play()` al abrir el reproductor.

Si el script externo no carga, el componente usa `track.spotify_embed_url` como iframe de respaldo con `autoplay=1` y conserva:

```html
allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
```

Spotify documenta que `play()` puede no iniciar automaticamente en todos los navegadores o usuarios por politicas de autoplay. Tambien documenta que retirar `encrypted-media` puede limitar la reproduccion a previews.

El reproductor se abre desde `/artistas` y `/canciones`. Si la cancion no tiene una URL de Spotify resoluble, no se muestra el boton de reproduccion embebida.

`PlatformLinksModal` muestra los links disponibles en `track.platforms` para abrir otras plataformas en una pestaña nueva.

## Rutas publicas

- `/artistas`: resumen por artista con canciones destacadas.
- `/canciones`: catalogo completo, agrupado en frontend por artista y release.
- `/releases`: catalogo publico de lanzamientos.
- `/releases/{slug}`: detalle publico del lanzamiento con canciones, reproductor Spotify y links de plataformas.
- `/tracks`: se conserva como compatibilidad y usa el mismo payload que `/canciones`.

Filtros iniciales soportados en `/canciones`:

- `artist={artist_slug}`
- `release={release_slug}`
- `search={texto}`

La pagina recibe `filterOptions.artists` y `filterOptions.releases` desde `PublicMusicCatalogService::filterOptions()` para mostrar selects publicos sin consultar el panel administrativo.
