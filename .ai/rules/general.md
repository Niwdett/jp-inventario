---
paths:
  - '.claude/**'
---

# General

## Impeccable skill vendorizado sin hooks
.claude/skills/impeccable/ (+ .claude/agents/impeccable-*.md) es una copia vendorizada de pbakaus/impeccable v4.1.2. Se instaló SIN hooks a propósito: no existe .claude/settings.json y no debe añadirse el PostToolUse/Stop que ejecuta scripts/hook.mjs en cada edición sin pedirlo al usuario. Los comandos (/impeccable shape|audit|critique|animate|polish|...) y su detector CLI (node .claude/skills/impeccable/scripts/detect.mjs) solo corren cuando se invocan. Stack real: Blade + Tailwind v4; los detectores pueden dar ruido sobre .blade.php.
