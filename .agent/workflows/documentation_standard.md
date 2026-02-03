---
description: Standard workflow for Documentation and Tooltips enforcement
---

# Documentation and Tooltips Standard

Every time a significant feature is implemented or modified, you MUST follow these steps to ensure complete documentation and user guidance.

## 1. Tooltips Implementation
- **Requirement:** ALL new interactive elements (buttons, inputs, status icons) MUST have a `title` attribute or a Bootstrap tooltip.
- **Format:** The tooltip should clearly explain:
  - What the element does (e.g., "Saves the current row").
  - What happens on click (e.g., "Calculates difference based on inputs").
  - Any conditions (e.g., "Enabled only when difference is 0").

## 2. Documentation Update
- **Requirement:** Create or update a specific Markdown documentation file in the `/documentacion` directory (create if it doesn't exist).
- **Naming Convention:** Use descriptive names in Spanish, e.g., `/documentacion/Mejoras_Vista_Tabla.md`.
- **Content Structure (Mandatory):**
  1. **Descripción General:** High-level summary of the feature.
  2. **Flujo de Usuario (User Flow):**
     - Step-by-step description of user actions.
     - **Diagram:** MUST include a Mermaid flowchart (`graph TD` or `sequenceDiagram`) visualizing the process.
  3. **Lógica Técnica Detallada:**
     - Explanation of algorithms (e.g., heuristics, math formulas).
     - Key files and functions modified.
     - Database changes (if any).
  4. **Estadísticas e Impacto:**
     - What metric does this improve? (e.g., "Reduces validation time by 40%").
     - Expected load/performance implications.
  5. **Guía de Uso (Paso a Paso):** Instructions with screenshots (if applicable) or clear text for the end-user.


## 3. Workflow Execution
- Run this check at the end of every task:
  - [ ] Are all new buttons handling `title` attributes?
  - [ ] Is there a `.md` file in `/documentacion` describing these changes?

---
*This workflow is mandatory for all UI/Logic tasks.*
