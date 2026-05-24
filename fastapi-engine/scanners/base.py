"""Clase base de los scanners."""


class ScannerBase:
    nombre: str = "scanner"
    descripcion: str = ""

    async def run(self, objetivo, parametros):
        raise NotImplementedError
