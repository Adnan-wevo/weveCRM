# SOFTWARE REQUIREMENTS SPECIFICATION (SRS)

## Wevetel CRM -- Versatile CRM Platform with Call Integration and Dynamic Form Builder

Version: 3.0 Date: 2026 Author: Wevo System R&D

------------------------------------------------------------------------

# 1. Introduction

## 1.1 Purpose

This document defines the Software Requirements Specification (SRS) for
**Wevetel CRM**, a versatile Customer Relationship Management platform
designed to manage customer interactions, track leads, monitor sales
pipelines, and integrate telephony communication within a unified
system.

The system emphasizes two key differentiators:

-   Integrated Call Management Module
-   Dynamic Drag-and-Drop Form Builder

These capabilities allow organizations to customize their CRM workflows
without requiring developer intervention while enabling real-time
communication management.

------------------------------------------------------------------------

## 1.2 Scope

Wevetel CRM provides a centralized platform for organizations to manage:

-   Customer data
-   Leads and opportunities
-   Sales pipeline activities
-   Communication interactions
-   Call logs and telephony integration
-   Custom data collection forms
-   Operational analytics and reporting

The system is designed to be modular and adaptable, allowing businesses
across various industries to configure workflows according to their
operational requirements.

------------------------------------------------------------------------

## 1.3 Definitions

  Term                    Definition
  ----------------------- -----------------------------------------------------
  CRM                     Customer Relationship Management system
  Lead                    A potential customer or enquiry
  Contact                 An individual or organization stored in CRM
  Pipeline                Sales or opportunity tracking stages
  Telephony Integration   Integration with PBX or VoIP systems
  Form Builder            Tool for creating customizable forms
  Agent                   User responsible for handling calls or interactions
  Admin                   User responsible for configuring the system

------------------------------------------------------------------------

## 1.4 Implementation Constraints

Implementation Note:
- All development shall reuse the repository's existing modules, patterns and providers. Do not introduce new top-level architecture or global provider changes.
- Features described in this SRS (Contacts, Leads, Pipeline, Calls, Form Builder, Reports) will be implemented within the existing Modules/ directory (for example `Modules/CRM`, `Modules/AccessControl`, `Modules/OrganisationSetup`) and using existing Livewire components, Flux UI conventions, and Reverb/Echo broadcasting.
- Follow the commands and workflows described in README.md and docs/; use the Docker-based development flow and the project's artisan wrapper. Do not change the project's architecture or service registration.
- All code must include migrations, factories, Pest tests, and follow `vendor/bin/pint` formatting before merging.

Module policy:
- The `Base CRM` functionality (contacts, leads, pipeline, basic call logging) and the `Form Builder` are mandatory core features and must be implemented in the central codebase under `Modules/CRM` (or appropriate existing modules).
- Additional domain-specific modules (for example a dedicated `Sales` module with specialized workflows) are optional and should be provided per-client as separate modules inside `Modules/` when requested. These optional modules must still follow repository conventions and not modify global bootstrap providers.


# 2. Product Overview

## 2.1 Product Perspective

Wevetel CRM is designed as a modular CRM platform capable of supporting
multiple business workflows including:

-   Sales operations
-   Customer support
-   Telemarketing
-   Customer service
-   Lead management

The system integrates communication systems such as PBX / Asterisk
telephony, enabling call interactions to be managed directly within the
CRM interface.

------------------------------------------------------------------------

## 2.2 Unique Value Proposition

### Native Call Integration

The system integrates directly with telephony platforms, enabling users
to:

-   Receive incoming call notifications
-   Initiate calls using click-to-call
-   Automatically log call activities
-   Associate calls with customer records

### Dynamic Drag-and-Drop Form Builder

Administrators can design custom forms using a drag-and-drop interface.
This allows businesses to configure the CRM to match their workflow
requirements without modifying the source code.

### Versatile CRM Architecture

The platform can be adapted for sales, support, telemarketing, and
customer service workflows.

------------------------------------------------------------------------

# 3. System Modules

## Dashboard Module

Provides an overview of system activities.

Features:

-   Quick actions
-   Key metrics display
-   Recent activities
-   Sales trends
-   Call statistics

------------------------------------------------------------------------

## Contact Management

Functions:

-   Create and manage customer profiles
-   Store contact history
-   Link calls and activities to contacts
-   Search and filter records

------------------------------------------------------------------------

## Lead Management

Features:

-   Lead capture
-   Lead assignment
-   Lead status tracking
-   Conversion to customer

------------------------------------------------------------------------

## Pipeline Management

Allows users to monitor business opportunities.

Features:

-   Sales stages
-   Deal progress tracking
-   Pipeline visualization

------------------------------------------------------------------------

## Communication Module

Tracks interactions such as:

-   Calls
-   Follow-ups
-   Activity history

------------------------------------------------------------------------

## Call Management Module

Telephony features include:

-   Incoming call popup
-   Click-to-call
-   Call answer or reject
-   Automatic call logging
-   Call history linked to contacts
-   Telephony connection status

------------------------------------------------------------------------

## Dynamic Form Builder

Allows administrators to create custom forms using drag-and-drop.

Supported fields:

-   Text
-   Number
-   Dropdown
-   Checkbox
-   Radio button
-   Date
-   Text area
-   File upload

Capabilities:

-   Custom field creation
-   Validation rules
-   Reusable templates
-   Module-specific forms

------------------------------------------------------------------------

## Reports and Analytics

Reports include:

-   Lead statistics
-   Pipeline analytics
-   Call volume reports
-   User activity reports

------------------------------------------------------------------------

# 4. Functional Requirements

## CRM Core

FR-001\
The system shall allow users to create and manage customer records.

FR-002\
The system shall store customer interaction history.

FR-003\
The system shall track leads and opportunities.

------------------------------------------------------------------------

## Telephony Integration

FR-004\
The system shall integrate with PBX or telephony systems.

FR-005\
The system shall display incoming call notifications.

FR-006\
The system shall allow click-to-call functionality.

FR-007\
The system shall log call activities automatically.

FR-008\
The system shall link call records with customer profiles.

------------------------------------------------------------------------

## Dynamic Form Builder

FR-009\
Administrators shall be able to create forms using drag-and-drop
components.

FR-010\
Administrators shall be able to configure field validation rules.

FR-011\
The system shall store form configuration dynamically.

FR-012\
Forms shall be assignable to specific CRM modules.

------------------------------------------------------------------------

# 5. Non Functional Requirements

## Performance

The system shall support multiple concurrent users.

## Security

The system shall implement role-based access control.

## Scalability

The system shall support modular system expansion.

## Usability

The system interface shall be intuitive and user-friendly.

------------------------------------------------------------------------

# 6. Future Enhancements

Possible future improvements include:

-   Omnichannel communication integration
-   AI call transcription
-   Workflow automation
-   Multi-tenant CRM deployment
-   Advanced analytics

------------------------------------------------------------------------

# End of Document
