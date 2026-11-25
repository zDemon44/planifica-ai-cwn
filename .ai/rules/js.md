---
paths:
  - '{resources/views/**,public/js/**}'
---

# Js

## Cambiar un select por código exige disparar change
public/js/selects.js reemplaza visualmente los select (salvo .curriculum-skill-select, .curriculum-indicator-select y [data-native-select]) y solo se redibuja con el evento change o al cambiar sus opciones. Si un script asigna select.value, debe disparar `change` (o el valor visible queda desactualizado). En el copiloto se usa sincronizarSelect() con una bandera para no repetir sus efectos.
