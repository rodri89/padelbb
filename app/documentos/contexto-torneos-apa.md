# Especificación de Draws – APA Amateur

## Propósito

Este documento define la **lógica oficial de generación de draws** para torneos amateur, basada **exclusivamente** en el Reglamento Deportivo Amateur de la Asociación Pádel Argentino (APA), edición 20/02/2023.

Este archivo es la **fuente de verdad** del proyecto. Cualquier lógica de generación de cuadros que no cumpla con lo aquí definido debe considerarse incorrecta.

---

## Alcance

* Torneos Amateur APA
* Categorías con sistema de zonas + eliminación
* Cantidad de parejas: **6 a 17** (primer bloque implementado)

Fuera de alcance:

* Sanciones disciplinarias
* Validaciones médicas
* Indumentaria
* Decisiones discrecionales del Colegio de Fiscales

---

## Convenciones utilizadas

* `1a`, `2b`, etc: posición final en zona

  * Número: posición (1° o 2°)
  * Letra: zona
* `/` indica **partido previo** (el ganador avanza)
* Una posición sin `/` indica **bye** (espera rival)

Ejemplo:

```
1a, 2b/2c
```

→ `1a` espera al ganador de `2b vs 2c`

---

## Reglas generales

* La unidad básica del torneo es la **zona**.
* Las zonas son preferentemente de **3 parejas**.
* Las zonas de **4 parejas** existen solo por excepción matemática.
* Si existen zonas de 4, **se generan siempre primero**.
* Todas las zonas clasifican **2 parejas**.

---

## Draws según cantidad de parejas

### ▶ 6, 7 u 8 parejas

* Zonas: 2

  * De 3 o 4 según disponibilidad
  * Si hay zona de 4, es la **zona A**
* Clasificados: 4
* No hay byes

**Orden de cruces:**

```
1a, 2b, 2a, 1b
```

**Interpretación:**

* Partido 1: 1a vs 2b
* Partido 2: 2a vs 1b

---

### ▶ 9, 10 u 11 parejas

* Zonas: 3
* Clasificados: 6
* Se reducen a 4 mediante partidos previos

**Orden de cruces:**

```
1a, 2b/2c, 1c/2a, 1b
```

**Interpretación:**

* 1a y 1b reciben bye
* Partido previo 1: 2b vs 2c
* Partido previo 2: 1c vs 2a
* Semifinales:

  * 1a vs ganador (2b/2c)
  * 1b vs ganador (1c/2a)

---

### ▶ 12, 13 o 14 parejas

* Zonas: 4
* Clasificados: 8
* Llave completa (sin byes)

**Orden de cruces:**

```
1a, 2c, 2b, 1d, 1c, 2a, 2d, 1b
```

**Interpretación:**

* Partido 1: 1a vs 2c
* Partido 2: 2b vs 1d
* Partido 3: 1c vs 2a
* Partido 4: 2d vs 1b

---

### ▶ 15, 16 o 17 parejas

* Zonas: 5
* Clasificados: 10
* Se reducen a 8 mediante partidos previos

**Orden de cruces:**

```
1a, 2b/2c, 1d, 1e, 1c, 2e, 2a/2d, 1b
```

**Interpretación:**

* Byes: 1a, 1b, 1c, 1d, 1e
* Partidos previos:

  * 2b vs 2c → enfrenta a 1a
  * 2e → enfrenta a 1c
  * 2a vs 2d → enfrenta a 1b

---

## Notas finales

* El orden de cruces es **estructural y fijo**.
* No debe ser alterado por ranking, provincias ni criterios externos.
* Este documento debe utilizarse como referencia para:

  * Implementación de lógica
  * Tests automáticos
  * Validación de bugs
  * Contexto inicial para IA

---

**Estado:** Parcial – bloques 6 a 17 parejas definidos y validados.

### ▶ 18, 19 o 20 parejas

* Zonas: 6
* Clasificados: 12
* Se reducen a 8 mediante partidos previos

**Orden de cruces:**

```
1a, 2f/2c, 1e/2b, 1d, 1c, 2a/1f, 2d/2e, 1b
```

**Interpretación:**

* Byes: 1a, 1d, 1c, 1b
* Partidos previos:

  * 2f vs 2c → enfrenta a 1a
  * 1e vs 2b → su ganador avanza
  * 2a vs 1f → su ganador avanza
  * 2d vs 2e → enfrenta a 1b
* El resultado final conforma una llave de 8 parejas.

---

**Estado:** Parcial – bloques 6 a 20 parejas definidos y validados.

Siguiente bloque pendiente: **21 a 32 parejas**.

---

### ▶ 21, 22 o 23 parejas

⚠️ **Nota sobre el reglamento APA**
El Reglamento APA presenta ambigüedades e inconsistencias gráficas para esta cantidad de parejas.
Por tal motivo, se define el siguiente **criterio de implementación**, manteniendo la lógica general de APA y respetando el orden deportivo de los clasificados.

---

### 🏆 Criterio de implementación adoptado

* Zonas: **A a G** (7 zonas)
* Clasificados: **14** (1° y 2° de cada zona)
* Objetivo: reducir a **8 parejas** (cuartos de final)

#### 🔹 Byes a cuartos

* **1a** y **1b**
* Se ubican en **extremos opuestos del draw**
* **Solo pueden cruzarse en la final**

#### 🔹 Ronda previa (12 parejas → 6 ganadores)

**Orden de cruces:**

```
1a,
1b,
1g/2a,
1f/2b,
1e/2c,
1d/2d,
1c/2e,
2f/2g
```

**Lectura:**

* 1a y 1b avanzan directamente a cuartos
* Los ganadores de los cruces alimentan cada mitad del cuadro
* No hay cruces entre parejas de la misma zona
* Se respeta jerarquía deportiva y balance del draw

---

