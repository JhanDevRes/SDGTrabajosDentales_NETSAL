#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Conversor de Markdown a Word (.docx) para la documentación del sistema NETSAL.

Genera un documento con portada, índice automático, títulos jerárquicos,
tablas con encabezado sombreado y bloques de código monoespaciados.

Uso:
    python docs/md_a_docx.py docs/OPTIMIZACION_CONSULTAS.md docs/OPTIMIZACION_CONSULTAS.docx
"""

import re
import sys
from pathlib import Path

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

AZUL = RGBColor(0x10, 0x2A, 0x43)
AZUL_MEDIO = RGBColor(0x1B, 0x65, 0xA6)
GRIS = RGBColor(0x46, 0x53, 0x6B)
GRIS_CLARO = "F2F4F7"


# ----------------------------------------------------------------------------
# Utilidades de formato
# ----------------------------------------------------------------------------

def configurar_documento(doc: Document) -> None:
    """Márgenes, tipografía base y estilos de párrafo."""
    for seccion in doc.sections:
        seccion.top_margin = Cm(2.5)
        seccion.bottom_margin = Cm(2.5)
        seccion.left_margin = Cm(2.5)
        seccion.right_margin = Cm(2.5)

    estilo = doc.styles["Normal"]
    estilo.font.name = "Calibri"
    estilo.font.size = Pt(10.5)
    estilo.font.color.rgb = RGBColor(0x16, 0x20, 0x2B)
    estilo.paragraph_format.space_after = Pt(6)
    estilo.paragraph_format.line_spacing = 1.15

    for nombre, tamano in (("Heading 1", 16), ("Heading 2", 13), ("Heading 3", 11.5)):
        h = doc.styles[nombre]
        h.font.name = "Calibri"
        h.font.size = Pt(tamano)
        h.font.bold = True
        h.font.color.rgb = AZUL
        h.paragraph_format.space_before = Pt(14)
        h.paragraph_format.space_after = Pt(6)
        h.paragraph_format.keep_with_next = True


def sombrear(celda_o_parrafo, color: str) -> None:
    """Aplica un sombreado a una celda o a un párrafo."""
    elemento = celda_o_parrafo._tc if hasattr(celda_o_parrafo, "_tc") else celda_o_parrafo._p
    propiedades = elemento.find(qn("w:tcPr")) if hasattr(celda_o_parrafo, "_tc") else elemento.get_or_add_pPr()
    sombra = OxmlElement("w:shd")
    sombra.set(qn("w:val"), "clear")
    sombra.set(qn("w:color"), "auto")
    sombra.set(qn("w:fill"), color)
    propiedades.append(sombra)


def agregar_numero_pagina(doc: Document) -> None:
    """Pie de página con el número de página."""
    for seccion in doc.sections:
        pie = seccion.footer.paragraphs[0]
        pie.alignment = WD_ALIGN_PARAGRAPH.CENTER
        pie.text = ""

        campo = OxmlElement("w:fldSimple")
        campo.set(qn("w:instr"), "PAGE")
        pie._p.append(campo)


def agregar_indice(doc: Document) -> None:
    """Inserta un campo de índice (Word lo rellena al actualizar campos)."""
    titulo = doc.add_heading("Índice de contenido", level=1)

    parrafo = doc.add_paragraph()
    inicio = OxmlElement("w:fldChar")
    inicio.set(qn("w:fldCharType"), "begin")

    instruccion = OxmlElement("w:instrText")
    instruccion.set(qn("xml:space"), "preserve")
    instruccion.text = 'TOC \\o "1-2" \\h \\z \\u'

    separador = OxmlElement("w:fldChar")
    separador.set(qn("w:fldCharType"), "separate")

    texto = OxmlElement("w:t")
    texto.text = "Actualice este índice con clic derecho > Actualizar campo (F9)."

    fin = OxmlElement("w:fldChar")
    fin.set(qn("w:fldCharType"), "end")

    run = parrafo.add_run()
    run._r.append(inicio)
    run._r.append(instruccion)
    run._r.append(separador)
    run._r.append(texto)
    run._r.append(fin)

    doc.add_page_break()


# ----------------------------------------------------------------------------
# Conversión de contenido
# ----------------------------------------------------------------------------

NEGRITA = re.compile(r"\*\*(.+?)\*\*")
CODIGO_EN_LINEA = re.compile(r"`([^`]+)`")
CURSIVA = re.compile(r"(?<!\*)\*([^*]+)\*(?!\*)")


def escribir_texto(parrafo, texto: str) -> None:
    """Agrega texto interpretando negrita, código y cursiva."""
    posiciones = []

    for patron, tipo in ((NEGRITA, "b"), (CODIGO_EN_LINEA, "c"), (CURSIVA, "i")):
        for coincidencia in patron.finditer(texto):
            posiciones.append((coincidencia.start(), coincidencia.end(), tipo, coincidencia.group(1)))

    posiciones.sort(key=lambda x: x[0])

    cursor = 0
    for inicio, fin, tipo, contenido in posiciones:
        if inicio < cursor:
            continue
        if inicio > cursor:
            parrafo.add_run(texto[cursor:inicio])

        run = parrafo.add_run(contenido)

        if tipo == "b":
            run.bold = True
        elif tipo == "i":
            run.italic = True
        else:
            run.font.name = "Consolas"
            run.font.size = Pt(9.5)

        cursor = fin

    if cursor < len(texto):
        parrafo.add_run(texto[cursor:])


def agregar_codigo(doc: Document, lineas: list[str]) -> None:
    """Bloque de código con fondo gris y tipografía monoespaciada."""
    for indice, linea in enumerate(lineas):
        parrafo = doc.add_paragraph()
        parrafo.paragraph_format.space_after = Pt(0)
        parrafo.paragraph_format.space_before = Pt(4) if indice == 0 else Pt(0)
        parrafo.paragraph_format.left_indent = Cm(0.4)
        parrafo.paragraph_format.line_spacing = 1.0
        sombrear(parrafo, GRIS_CLARO)

        run = parrafo.add_run(linea if linea.strip() else " ")
        run.font.name = "Consolas"
        run.font.size = Pt(8.5)
        run.font.color.rgb = RGBColor(0x1F, 0x2D, 0x3D)


def agregar_tabla(doc: Document, filas: list[list[str]]) -> None:
    """Tabla con encabezado sombreado y bordes."""
    columnas = max(len(f) for f in filas)

    tabla = doc.add_table(rows=0, cols=columnas)
    tabla.style = "Table Grid"
    tabla.alignment = WD_TABLE_ALIGNMENT.CENTER

    for numero, fila in enumerate(filas):
        celdas = tabla.add_row().cells

        for posicion in range(columnas):
            texto = fila[posicion] if posicion < len(fila) else ""
            celdas[posicion].text = ""

            parrafo = celdas[posicion].paragraphs[0]
            parrafo.paragraph_format.space_after = Pt(2)
            escribir_texto(parrafo, texto)

            for run in parrafo.runs:
                run.font.size = Pt(9)

            if numero == 0:
                for run in parrafo.runs:
                    run.bold = True
                    run.font.color.rgb = AZUL
                sombrear(celdas[posicion], "E8EDF3")

    doc.add_paragraph().paragraph_format.space_after = Pt(2)


def es_separador_tabla(linea: str) -> bool:
    return bool(re.fullmatch(r"\|[\s:\-|]+\|", linea.strip()))


def convertir(md: str, destino: Path) -> None:
    doc = Document()
    configurar_documento(doc)
    agregar_numero_pagina(doc)

    lineas = md.splitlines()
    indice = 0
    primera_seccion = True
    portada_hecha = False

    while indice < len(lineas):
        linea = lineas[indice]
        limpia = linea.rstrip()

        # Bloques de código
        if limpia.startswith("```"):
            indice += 1
            bloque = []
            while indice < len(lineas) and not lineas[indice].startswith("```"):
                bloque.append(lineas[indice])
                indice += 1
            indice += 1
            agregar_codigo(doc, bloque)
            continue

        # Tablas
        if limpia.startswith("|") and indice + 1 < len(lineas) and es_separador_tabla(lineas[indice + 1]):
            filas = []
            encabezado = [c.strip() for c in limpia.strip("|").split("|")]
            filas.append(encabezado)
            indice += 2

            while indice < len(lineas) and lineas[indice].strip().startswith("|"):
                filas.append([c.strip() for c in lineas[indice].strip().strip("|").split("|")])
                indice += 1

            agregar_tabla(doc, filas)
            continue

        # Separador horizontal
        if re.fullmatch(r"-{3,}", limpia.strip()):
            indice += 1
            continue

        # Títulos
        if limpia.startswith("# "):
            texto = limpia[2:].strip()

            if not portada_hecha:
                # Portada: título, metadatos y salto de página
                titulo = doc.add_paragraph()
                titulo.alignment = WD_ALIGN_PARAGRAPH.CENTER
                titulo.paragraph_format.space_before = Pt(120)
                run = titulo.add_run(texto)
                run.bold = True
                run.font.size = Pt(26)
                run.font.color.rgb = AZUL

                indice += 1
                while indice < len(lineas) and not lineas[indice].strip().startswith("---"):
                    detalle = lineas[indice].strip()
                    if detalle:
                        parrafo = doc.add_paragraph()
                        parrafo.alignment = WD_ALIGN_PARAGRAPH.CENTER
                        parrafo.paragraph_format.space_after = Pt(2)
                        escribir_texto(parrafo, detalle)
                        for r in parrafo.runs:
                            r.font.size = Pt(10.5)
                            r.font.color.rgb = GRIS
                    indice += 1

                doc.add_page_break()
                agregar_indice(doc)
                portada_hecha = True
                continue

            doc.add_heading(texto, level=1)
            primera_seccion = False
            indice += 1
            continue

        if limpia.startswith("## "):
            doc.add_heading(limpia[3:].strip(), level=2)
            indice += 1
            continue

        if limpia.startswith("### "):
            doc.add_heading(limpia[4:].strip(), level=3)
            indice += 1
            continue

        # Listas
        if re.match(r"^\s*[-*]\s+", limpia):
            texto = re.sub(r"^\s*[-*]\s+", "", limpia)
            parrafo = doc.add_paragraph(style="List Bullet")
            escribir_texto(parrafo, texto)
            indice += 1
            continue

        if re.match(r"^\s*\d+\.\s+", limpia):
            texto = re.sub(r"^\s*\d+\.\s+", "", limpia)
            parrafo = doc.add_paragraph(style="List Number")
            escribir_texto(parrafo, texto)
            indice += 1
            continue

        # Párrafo normal
        if limpia.strip():
            parrafo = doc.add_paragraph()
            escribir_texto(parrafo, limpia.strip())

        indice += 1

    destino.parent.mkdir(parents=True, exist_ok=True)
    doc.save(destino)


def main() -> int:
    if len(sys.argv) < 3:
        print("Uso: python docs/md_a_docx.py <origen.md> <destino.docx>")
        return 1

    origen = Path(sys.argv[1])
    destino = Path(sys.argv[2])

    if not origen.exists():
        print(f"No se encontró el archivo: {origen}")
        return 1

    convertir(origen.read_text(encoding="utf-8"), destino)
    print(f"Documento generado: {destino}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
