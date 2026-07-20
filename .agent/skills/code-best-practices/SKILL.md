---
name: code-best-practices
description: Instructs the agent to apply software engineering best practices when generating or reviewing code, including clean architecture, test coverage, and maintainability guidelines.
license: Apache-2.0
metadata:
  author: mbuyco
  version: "1.0"
allowed-tools: Read
---

# Code Best Practices Skill

## Overview

Encodes core software engineering quality practices such as clean code principles, modular design, test guidance, and maintainability criteria.

## When to use

- Code generation requests
- Code review tasks
- Maintainability assessments
- Test creation and coverage evaluation

## Instructions

1. Enforce naming conventions and modular structure.
2. Apply SOLID and separation of concerns.
3. Generate or recommend tests (unit/integration).
4. Provide comments and documentation suggestions.
5. Call out anti-patterns and code smells.

## Example

**Input:** “Write a REST handler for user registration in Python.”
**Output:**
- Code with structured error handling
- Explicit interfaces and test scaffolding
- Documentation inline and externally
