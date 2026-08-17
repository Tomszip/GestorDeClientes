import { Component } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  standalone: false,
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css',
})
export class DashboardComponent {

  metricas = [
    { titulo: 'Chats Activos',        valor: '18',  cambio: '+5 hoy',          icono: 'bi-chat-dots-fill',   bgColor: '#EFF6FF', textColor: '#2563EB' },
    { titulo: 'Nuevos Contactos',     valor: '142', cambio: '+12 este mes',    icono: 'bi-person-plus-fill', bgColor: '#F0FDF4', textColor: '#16A34A' },
    { titulo: 'Tickets Cerrados',     valor: '89',  cambio: '+23 esta semana', icono: 'bi-check-circle-fill',bgColor: '#FAF5FF', textColor: '#9333EA' },
    { titulo: 'Archivos Compartidos', valor: '347', cambio: '+45 esta semana', icono: 'bi-folder2-open',     bgColor: '#FFF7ED', textColor: '#EA580C' },
  ];

  actividades = [
    { nombre: 'María González',   accion: 'inició un nuevo chat',           tiempo: 'Hace 5 minutos',  inicial: 'M' },
    { nombre: 'Carlos Rodríguez', accion: 'cerró un ticket de soporte',     tiempo: 'Hace 18 minutos', inicial: 'C' },
    { nombre: 'Ana Martínez',     accion: 'se registró como nueva clienta', tiempo: 'Hace 1 hora',     inicial: 'A' },
    { nombre: 'Luis Fernández',   accion: 'subió un nuevo archivo',         tiempo: 'Hace 2 horas',    inicial: 'L' },
    { nombre: 'Sofía López',      accion: 'actualizó su información',       tiempo: 'Hace 3 horas',    inicial: 'S' },
  ];

  archivos = [
    { nombre: 'Propuesta_Comercial_2026.pdf', tipo: 'PDF',  tamano: '2.4 MB', fecha: '01/02/2026', colorIcono: '#EF4444' },
    { nombre: 'Contrato_Servicio_Q1.pdf',     tipo: 'PDF',  tamano: '1.1 MB', fecha: '31/01/2026', colorIcono: '#EF4444' },
    { nombre: 'Presentacion_Clientes.pptx',   tipo: 'PPTX', tamano: '5.8 MB', fecha: '30/01/2026', colorIcono: '#2563EB' },
    { nombre: 'Base_Datos_Enero.xlsx',        tipo: 'XLSX', tamano: '890 KB', fecha: '28/01/2026', colorIcono: '#16A34A' },
  ];

  stats = [
    { titulo: 'Tiempo promedio de respuesta', valor: '12 min',  cambio: '-3 min', icono: 'bi-clock'     },
    { titulo: 'Tasa de resolución',           valor: '94%',     cambio: '+2%',    icono: 'bi-graph-up'  },
    { titulo: 'Satisfacción del cliente',     valor: '4.8 / 5', cambio: '+0.3',   icono: 'bi-star-fill' },
  ];
}
