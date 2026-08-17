export interface User {
  id: number;
  nombre: string;
  email: string;
  rol: 'Admin' | 'Usuario';
  estado: 'Activo' | 'Inactivo';
  ultimoAcceso?: string;
}

export interface Client {
  id: number;
  nombre: string;
  email: string;
  telefono: string;
  empresa: string;
  ubicacion?: string;
  estado: 'Activo' | 'Nuevo' | 'Pendiente' | 'Inactivo';
  ultimoContacto?: string;
  valorNegocios?: string;
}

export interface Message {
  id: number;
  texto: string;
  hora: string;
  esPropio: boolean;
  conversacionId: number;
}

export interface HistoryItem {
  fecha: string;
  accion: string;
}

export interface Conversation {
  id: number;
  nombre: string;
  ultimoMensaje: string;
  hora: string;
  sinLeer: number;
  estado: 'nuevo' | 'en-curso' | 'cerrado';
  email?: string;
  telefono?: string;
  empresa?: string;
  etiquetas?: string[];
  historial?: HistoryItem[];
}

export interface MetricCard {
  titulo: string;
  valor: string;
  cambio: string;
  icono: string;
  color: string;
}

export interface Activity {
  nombre: string;
  accion: string;
  tiempo: string;
  inicial: string;
}

export interface FileItem {
  nombre: string;
  tipo: string;
  tamaño: string;
  fecha: string;
  colorIcono: string;
}

export interface Contenido {
  id: number;
  titulo: string;
  texto?: string;
  tipo: 'texto' | 'imagen' | 'archivo';
  rutaArchivo?: string;
  fechaCreacion: string;
  categoriaId?: number | null;
  categoriaNombre?: string | null;
}

export interface Categoria {
  id: number;
  nombre: string;
  descripcion?: string;
}

export interface ContenidoCompartido extends Contenido {
  compartidoPor: string;
  fechaCompartido: string;
}

export interface UsuarioBusqueda {
  id: number;
  nombre: string;
  email: string;
}

export interface AuthRespuesta {
  token: string;
  user: User;
}

export interface Tema {
  id: number;
  nombre: string;
  archivoCss: string;
  activo?: boolean;
}

export interface ConfiguracionPublica {
  nombreSitio: string;
  temaActivo: {
    id: number;
    nombre: string;
    archivoCss: string;
  };
}

export interface ContenidoModeracion {
  id: number;
  titulo: string;
  texto?: string;
  tipo: 'texto' | 'imagen' | 'archivo';
  rutaArchivo?: string;
  fechaCreacion: string;
  estado: 'Visible' | 'Oculto';
  autor: string;
  autorEmail: string;
  categoriaNombre?: string | null;
}
