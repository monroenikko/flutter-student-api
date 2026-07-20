---
name: system-design
description: Guides the agent in analyzing, recommending, and documenting software system architecture and trade-offs. Use this skill for high-level design tasks such as component breakdowns, performance constraints, and architectural patterns.
license: Apache-2.0
metadata:
  author: mbuyco
  version: "1.0"
allowed-tools: Read
---

# System Design Skill

## Overview

This skill enables the agent to perform robust system design reasoning with explicit attention to quality attributes: performance, scalability, reliability, and security.

## When to use

Activate this skill when the task involves:
- Designing a system architecture
- Evaluating design trade-offs (performance vs cost vs complexity)
- Recommending architectural patterns
- Identifying non-functional requirements

## Instructions

1. Start by identifying the *contextual constraints* (scale, latency, consistency, security requirements).
2. Enumerate system components and interactions.
3. For each major quality attribute (performance, reliability, security), provide explicit reasoning on trade-offs.
4. Recommend patterns (e.g., microservices, event-driven, caching strategies) and justify selection.
5. Produce a structured design document (sections: Overview, Requirements, Components, Quality Attribute Analysis, Diagrams/References).

## Examples

**Input:** “Design a scalable document search system for 10M users with sub-second queries and secure access controls.”
**Output:**
- Requirements breakdown
- Quality attribute analysis
- Suggested architecture pattern
- Estimated throughput/latency

## Edge Cases

- Conflicting non-functional requirements
- Undefined performance constraints
