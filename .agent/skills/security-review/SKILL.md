---
name: security-review
description: Provides structured security analysis and threat modeling for given systems or outputs, integrating common threat categories and secure defaults. Use when evaluating or generating designs/code that require security confidence.
license: Apache-2.0
metadata:
  author: mbuyco
  version: "1.0"
allowed-tools: Read
---

# Security Review Skill

## Overview

Helps the agent perform systematic security evaluations using recognized threat analysis patterns (e.g., STRIDE), secure defaults, and threat mitigation recommendations.

## When to use

- Code, design, or interface evaluations
- Threat modeling
- Security hardening guidance
- Detecting insecure dependencies or misconfigurations

## Instructions

1. Identify assets and trust boundaries.
2. Enumerate threat categories (injection, elevation, exfiltration, misuse).
3. For each threat, provide impact, likelihood, and proposed mitigations.
4. Highlight secure defaults and configuration best practices.
5. Call out specific code or design patterns with high risk.

## Example

**Input:** “Evaluate the REST API design for authentication and session management.”
**Output:**
- Identified risks
- Threat analysis table
- Best practice recommendations

## Edge Cases

- Legacy systems with limited security controls
- Highly permissioned environments
