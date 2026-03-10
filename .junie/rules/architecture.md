# Architecture

The project follows Clean Architecture.

Directory structure:

src/

Domain/
Application/
Infrastructure/
UI/

Dependencies:

UI -> Application -> Domain

Infrastructure implements interfaces.

Domain must remain framework independent.
